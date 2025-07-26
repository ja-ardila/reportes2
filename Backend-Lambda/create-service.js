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
        'Access-Control-Allow-Methods': 'OPTIONS,POST'
    };
    
    if (event.httpMethod === 'OPTIONS') {
        return {
            statusCode: 200,
            headers,
            body: JSON.stringify({ message: 'OK' })
        };
    }
    
    try {
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
            userId = 1;
        }

        let requestBody;
        
        if (event.body) {
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
            requestBody = event;
        }
        
        const validationError = validateReporteData(requestBody);
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
            
            const [existingReporte] = await connection.execute(
                'SELECT id FROM reportes WHERE numero_reporte = ?',
                [requestBody.numero_reporte]
            );
            
            if (existingReporte.length > 0) {
                return {
                    statusCode: 409,
                    headers,
                    body: JSON.stringify({
                        error: 'El número de reporte ya existe',
                        code: 'DUPLICATE_REPORTE_NUMBER'
                    })
                };
            }
            
            await connection.beginTransaction();
            
            const fechaReporte = requestBody.fecha || new Date().toISOString().slice(0, 19).replace('T', ' ');
            
            const [reporteResult] = await connection.execute(
                `INSERT INTO reportes 
                (numero_reporte, usuario, fecha, empresa, nit, direccion, telefono, contacto, 
                email, ciudad, fecha_inicio, fecha_cierre, hora_inicio, hora_cierre, 
                servicio_reportado, tipo_servicio, informe, observaciones, 
                cedula_tecnico, nombre_tecnico, firma_tecnico, cedula_encargado, 
                nombre_encargado, id_usuario, firma_encargado, token) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
                [
                    requestBody.numero_reporte,
                    userRows[0].usuario,
                    fechaReporte,
                    requestBody.empresa || '',
                    requestBody.nit || '',
                    requestBody.direccion || '',
                    requestBody.telefono || '',
                    requestBody.contacto || '',
                    requestBody.email || '',
                    requestBody.ciudad || '',
                    requestBody.fecha_inicio || null,
                    requestBody.fecha_cierre || null,
                    requestBody.hora_inicio || null,
                    requestBody.hora_cierre || null,
                    requestBody.servicio_reportado || '',
                    requestBody.tipo_servicio || '',
                    requestBody.informe || '',
                    requestBody.observaciones || '',
                    requestBody.cedula_tecnico || '',
                    requestBody.nombre_tecnico || '',
                    requestBody.firma_tecnico || '',
                    requestBody.cedula_encargado || '',
                    requestBody.nombre_encargado || '',
                    userId,
                    requestBody.firma_encargado || '',
                    requestBody.token || ''
                ]
            );
            
            const reporteId = reporteResult.insertId;
            
            if (requestBody.imagenes && requestBody.imagenes.length > 0) {
                await processImages(requestBody.imagenes, reporteId, connection);
            }
            
            await connection.commit();
            
            const [newReporte] = await connection.execute(
                `SELECT r.*, u.nombre as creado_por 
                FROM reportes r 
                LEFT JOIN usuarios u ON r.id_usuario = u.id 
                WHERE r.id = ?`,
                [reporteId]
            );
            
            return {
                statusCode: 201,
                headers,
                body: JSON.stringify({
                    message: 'Reporte creado exitosamente',
                    reporte: newReporte[0],
                    reporteId: reporteId
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

function validateReporteData(data) {
    const requiredFields = ['numero_reporte'];
    
    for (const field of requiredFields) {
        if (!data[field] || data[field].toString().trim() === '') {
            return `El campo '${field}' es requerido`;
        }
    }
    
    if (data.fecha) {
        const fechaDateTimeRegex = /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/;
        const fechaDateRegex = /^\d{4}-\d{2}-\d{2}$/;
        if (!fechaDateTimeRegex.test(data.fecha) && !fechaDateRegex.test(data.fecha)) {
            return 'El formato de fecha debe ser YYYY-MM-DD HH:MM:SS o YYYY-MM-DD';
        }
    }
    
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
    
    if (data.numero_reporte.length > 50) {
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