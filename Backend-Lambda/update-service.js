const mysql = require('mysql2/promise');
const jwt = require('jsonwebtoken');

const dbConfig = {
    host: process.env.DB_HOST,
    user: process.env.DB_USER,
    password: process.env.DB_PASSWORD,
    database: process.env.DB_NAME,
    port: 3306
};

const JWT_SECRET = process.env.JWT_SECRET || 'tu-jwt-secret-key';

exports.handler = async (event) => {
    console.log('Event:', JSON.stringify(event, null, 2));
    
    const headers = {
        'Content-Type': 'application/json',
        'Access-Control-Allow-Origin': '*',
        'Access-Control-Allow-Headers': 'Content-Type,Authorization',
        'Access-Control-Allow-Methods': 'OPTIONS,PUT,PATCH'
    };
    
    if (event.httpMethod === 'OPTIONS') {
        return {
            statusCode: 200,
            headers,
            body: JSON.stringify({ message: 'OK' })
        };
    }
    
    try {
        // Verificar que el método sea PUT o PATCH (solo para API Gateway)
        if (event.httpMethod && event.httpMethod !== 'PUT' && event.httpMethod !== 'PATCH') {
            return {
                statusCode: 405,
                headers,
                body: JSON.stringify({ 
                    error: 'Método no permitido. Use PUT o PATCH',
                    code: 'METHOD_NOT_ALLOWED'
                })
            };
        }

        // Verificar autorización (opcional para invocación directa)
        let userId = null;
        
        if (event.headers && (event.headers.Authorization || event.headers.authorization)) {
            const authHeader = event.headers.Authorization || event.headers.authorization;
            if (!authHeader.startsWith('Bearer ')) {
                return {
                    statusCode: 401,
                    headers,
                    body: JSON.stringify({ 
                        error: 'Token de autorización requerido',
                        code: 'NO_TOKEN'
                    })
                };
            }
            
            const token = authHeader.substring(7);
            let decodedToken;
            
            try {
                decodedToken = jwt.verify(token, JWT_SECRET);
                userId = decodedToken.id_usuario;
            } catch (jwtError) {
                return {
                    statusCode: 401,
                    headers,
                    body: JSON.stringify({ 
                        error: 'Token inválido o expirado',
                        code: 'INVALID_TOKEN'
                    })
                };
            }
        } else {
            // Para testing directo, usar un usuario de prueba
            userId = 1;
        }

        // Obtener ID del reporte y datos
        let reporteId;
        let requestBody;
        
        if (event.pathParameters?.id) {
            // Invocación desde API Gateway
            reporteId = event.pathParameters.id;
            try {
                requestBody = JSON.parse(event.body);
            } catch (parseError) {
                return {
                    statusCode: 400,
                    headers,
                    body: JSON.stringify({ 
                        error: 'Formato JSON inválido',
                        code: 'INVALID_JSON'
                    })
                };
            }
        } else {
            // Invocación directa - el ID debe estar en el cuerpo del evento
            requestBody = event.body ? JSON.parse(event.body) : event;
            reporteId = requestBody.id;
            
            if (!reporteId) {
                return {
                    statusCode: 400,
                    headers,
                    body: JSON.stringify({ 
                        error: 'ID del reporte requerido',
                        code: 'MISSING_REPORTE_ID'
                    })
                };
            }
        }

        // Validar que no se intenten modificar campos prohibidos
        const forbiddenFields = ['id', 'fecha', 'firma_tecnico', 'firma_encargado'];
        const forbiddenFieldsFound = Object.keys(requestBody).filter(field => 
            forbiddenFields.includes(field)
        );

        if (forbiddenFieldsFound.length > 0) {
            return {
                statusCode: 400,
                headers,
                body: JSON.stringify({
                    error: `No se pueden modificar los siguientes campos: ${forbiddenFieldsFound.join(', ')}`,
                    code: 'FORBIDDEN_FIELDS'
                })
            };
        }

        const validationError = validateUpdateReporteData(requestBody);
        if (validationError) {
            return {
                statusCode: 400,
                headers,
                body: JSON.stringify({
                    error: validationError,
                    code: 'VALIDATION_ERROR'
                })
            };
        }
        
        const connection = await mysql.createConnection(dbConfig);
        
        try {
            // Verificar que el usuario existe
            const [userRows] = await connection.execute(
                'SELECT id, usuario, nombre, rol FROM usuarios WHERE id = ?',
                [userId]
            );
            
            if (userRows.length === 0) {
                return {
                    statusCode: 401,
                    headers,
                    body: JSON.stringify({ 
                        error: 'Usuario no encontrado',
                        code: 'USER_NOT_FOUND'
                    })
                };
            }

            // Verificar que el reporte existe
            const [existingReporte] = await connection.execute(
                'SELECT id, id_usuario FROM reportes WHERE id = ?',
                [reporteId]
            );
            
            if (existingReporte.length === 0) {
                return {
                    statusCode: 404,
                    headers,
                    body: JSON.stringify({
                        error: 'Reporte no encontrado',
                        code: 'REPORTE_NOT_FOUND'
                    })
                };
            }

            // Verificar permisos (opcional: solo el creador puede modificar)
            // Si quieres que cualquier usuario autorizado pueda modificar, comenta estas líneas
            if (existingReporte[0].id_usuario !== userId) {
                return {
                    statusCode: 403,
                    headers,
                    body: JSON.stringify({
                        error: 'No tienes permisos para modificar este reporte',
                        code: 'INSUFFICIENT_PERMISSIONS'
                    })
                };
            }

            // Verificar si se está intentando cambiar numero_reporte y que no exista ya
            if (requestBody.numero_reporte) {
                const [duplicateCheck] = await connection.execute(
                    'SELECT id FROM reportes WHERE numero_reporte = ? AND id != ?',
                    [requestBody.numero_reporte, reporteId]
                );
                
                if (duplicateCheck.length > 0) {
                    return {
                        statusCode: 409,
                        headers,
                        body: JSON.stringify({
                            error: 'El número de reporte ya existe',
                            code: 'DUPLICATE_REPORTE_NUMBER'
                        })
                    };
                }
            }

            await connection.beginTransaction();
            
            // Construir la consulta de actualización dinámicamente
            const updateFields = [];
            const updateValues = [];
            
            const allowedFields = [
                'numero_reporte', 'empresa', 'nit', 'direccion', 'telefono', 'contacto',
                'email', 'ciudad', 'fecha_inicio', 'fecha_cierre', 'hora_inicio', 
                'hora_cierre', 'servicio_reportado', 'tipo_servicio', 'informe', 
                'observaciones', 'cedula_tecnico', 'nombre_tecnico', 'cedula_encargado', 
                'nombre_encargado', 'token'
            ];

            for (const field of allowedFields) {
                if (requestBody.hasOwnProperty(field)) {
                    updateFields.push(`${field} = ?`);
                    updateValues.push(requestBody[field] || '');
                }
            }

            if (updateFields.length === 0) {
                return {
                    statusCode: 400,
                    headers,
                    body: JSON.stringify({
                        error: 'No se proporcionaron campos para actualizar',
                        code: 'NO_FIELDS_TO_UPDATE'
                    })
                };
            }

            // Agregar el ID del reporte al final para la cláusula WHERE
            updateValues.push(reporteId);

            const updateQuery = `UPDATE reportes SET ${updateFields.join(', ')} WHERE id = ?`;
            
            await connection.execute(updateQuery, updateValues);

            // Procesar imágenes si se proporcionaron
            if (requestBody.imagenes && requestBody.imagenes.length > 0) {
                // Eliminar imágenes existentes (opcional)
                await connection.execute('DELETE FROM imagenes WHERE id_reporte = ?', [reporteId]);
                
                // Agregar nuevas imágenes
                await processImages(requestBody.imagenes, reporteId, connection);
            }
            
            await connection.commit();
            
            // Devolver el reporte actualizado
            const [updatedReporte] = await connection.execute(
                `SELECT r.*, u.nombre as creado_por 
                FROM reportes r 
                LEFT JOIN usuarios u ON r.id_usuario = u.id 
                WHERE r.id = ?`,
                [reporteId]
            );
            
            return {
                statusCode: 200,
                headers,
                body: JSON.stringify({
                    message: 'Reporte actualizado exitosamente',
                    reporte: updatedReporte[0]
                })
            };
            
        } catch (dbError) {
            await connection.rollback();
            throw dbError;
        } finally {
            await connection.end();
        }
        
    } catch (error) {
        console.error('Error:', error);
        return {
            statusCode: 500,
            headers,
            body: JSON.stringify({
                error: 'Error interno del servidor',
                code: 'INTERNAL_ERROR',
                details: process.env.NODE_ENV === 'development' ? error.message : undefined
            })
        };
    }
};

function validateUpdateReporteData(data) {
    // Validaciones de formato de fecha
    if (data.fecha_inicio) {
        const fechaRegex = /^\d{4}-\d{2}-\d{2}$/;
        if (!fechaRegex.test(data.fecha_inicio)) {
            return 'El formato de fecha_inicio debe ser YYYY-MM-DD';
        }
    }
    
    if (data.fecha_cierre) {
        const fechaRegex = /^\d{4}-\d{2}-\d{2}$/;
        if (!fechaRegex.test(data.fecha_cierre)) {
            return 'El formato de fecha_cierre debe ser YYYY-MM-DD';
        }
    }
    
    if (data.hora_inicio) {
        const horaRegex = /^\d{2}:\d{2}:\d{2}$/;
        if (!horaRegex.test(data.hora_inicio)) {
            return 'El formato de hora_inicio debe ser HH:MM:SS';
        }
    }
    
    if (data.hora_cierre) {
        const horaRegex = /^\d{2}:\d{2}:\d{2}$/;
        if (!horaRegex.test(data.hora_cierre)) {
            return 'El formato de hora_cierre debe ser HH:MM:SS';
        }
    }
    
    // Validaciones de longitud
    if (data.numero_reporte && data.numero_reporte.length > 50) {
        return 'El número de reporte no puede exceder 50 caracteres';
    }
    
    if (data.empresa && data.empresa.length > 100) {
        return 'El nombre de la empresa no puede exceder 100 caracteres';
    }
    
    if (data.nombre_tecnico && data.nombre_tecnico.length > 100) {
        return 'El nombre del técnico no puede exceder 100 caracteres';
    }
    
    if (data.usuario && data.usuario.length > 50) {
        return 'El usuario no puede exceder 50 caracteres';
    }
    
    return null;
}

async function processImages(imagenes, reporteId, connection) {
    for (let i = 0; i < imagenes.length; i++) {
        const imagen = imagenes[i];
        
        if (!imagen.data) {
            console.log('Imagen sin data, saltando...');
            continue;
        }
        
        try {
            // Validar que sea base64 válido
            if (!isValidBase64(imagen.data)) {
                console.error(`Imagen con data base64 inválida, saltando...`);
                continue;
            }

            console.log(`Guardando imagen base64 en base de datos...`);
            
            await connection.execute(
                `INSERT INTO imagenes (id_reporte, ruta_imagen) VALUES (?, ?)`,
                [reporteId, imagen.data]
            );
            
        } catch (imageError) {
            console.error(`Error procesando imagen:`, imageError);
        }
    }
}

function isValidBase64(str) {
    try {
        return btoa(atob(str)) === str;
    } catch (err) {
        // Verificar si tiene el formato data:image/...;base64,
        const base64Regex = /^data:image\/(png|jpeg|jpg|gif);base64,/;
        if (base64Regex.test(str)) {
            const base64Data = str.split(',')[1];
            try {
                return btoa(atob(base64Data)) === base64Data;
            } catch (e) {
                return false;
            }
        }
        return false;
    }
}
