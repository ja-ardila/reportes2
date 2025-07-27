import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable, throwError } from 'rxjs';
import { catchError, retry } from 'rxjs/operators';

export interface Reporte {
  id?: number;
  numero_reporte?: string;
  empresa: string;
  nit: string;
  direccion: string;
  telefono: string;
  contacto: string;
  email: string;
  ciudad: string;
  fechai: string;      // fecha_inicio en la base de datos
  fechac: string;      // fecha_cierre en la base de datos
  horai: string;       // hora_inicio en la base de datos
  horac: string;       // hora_cierre en la base de datos
  servicior: string;   // servicio_reportado en la base de datos
  tiposervicio: string; // tipo_servicio en la base de datos
  informe: string;
  observaciones: string;
  cedulat: string;     // cedula_tecnico en la base de datos
  nombret: string;     // nombre_tecnico en la base de datos
  firma: string;       // firma_tecnico en la base de datos
  cedulae: string;     // cedula_encargado en la base de datos
  nombree: string;     // nombre_encargado en la base de datos
  signature?: string;  // firma_encargado en base64
  fecha: string;       // fecha_creacion
  usuario: string;
}

export interface Usuario {
  id: number;
  nombre: string;
  rol: string;
  firma_path?: string;
}

export interface ImagenReporte {
  id: number;
  id_reporte: number;
  ruta_imagen: string;
  nombre_original?: string;
  fecha_subida?: string;
}

export interface ApiResponse<T> {
  success: boolean;
  message: string;
  data?: T;
  error?: string;
}

@Injectable({
  providedIn: 'root'
})
export class ReportesLambdaService {
  
  // URL base de AWS API Gateway (cambiar por la URL real)
  private readonly API_BASE_URL = 'https://your-api-gateway-url.execute-api.region.amazonaws.com/prod';
  
  private httpOptions = {
    headers: new HttpHeaders({
      'Content-Type': 'application/json'
    })
  };

  constructor(private http: HttpClient) { }

  // ============ REPORTES ============

  /**
   * Obtener reporte por ID
   */
  getReporte(id: number): Observable<ApiResponse<Reporte>> {
    return this.http.get<ApiResponse<Reporte>>(`${this.API_BASE_URL}/reportes/${id}`)
      .pipe(
        retry(2),
        catchError(this.handleError)
      );
  }

  /**
   * Obtener todos los reportes
   */
  getReportes(): Observable<ApiResponse<Reporte[]>> {
    return this.http.get<ApiResponse<Reporte[]>>(`${this.API_BASE_URL}/reportes`)
      .pipe(
        retry(2),
        catchError(this.handleError)
      );
  }

  /**
   * Crear nuevo reporte
   */
  crearReporte(reporte: Reporte): Observable<ApiResponse<{id: number, numero_reporte: string}>> {
    return this.http.post<ApiResponse<{id: number, numero_reporte: string}>>(
      `${this.API_BASE_URL}/reportes`, 
      reporte, 
      this.httpOptions
    ).pipe(
      retry(2),
      catchError(this.handleError)
    );
  }

  /**
   * Actualizar reporte existente
   */
  actualizarReporte(id: number, reporte: Reporte): Observable<ApiResponse<any>> {
    return this.http.put<ApiResponse<any>>(
      `${this.API_BASE_URL}/reportes/${id}`, 
      reporte, 
      this.httpOptions
    ).pipe(
      retry(2),
      catchError(this.handleError)
    );
  }

  /**
   * Eliminar reporte
   */
  eliminarReporte(id: number): Observable<ApiResponse<any>> {
    return this.http.delete<ApiResponse<any>>(`${this.API_BASE_URL}/reportes/${id}`)
      .pipe(
        retry(2),
        catchError(this.handleError)
      );
  }

  // ============ USUARIOS Y TÉCNICOS ============

  /**
   * Obtener lista de técnicos para el dropdown
   */
  getTecnicos(): Observable<ApiResponse<Usuario[]>> {
    return this.http.get<ApiResponse<Usuario[]>>(`${this.API_BASE_URL}/usuarios/tecnicos`)
      .pipe(
        retry(2),
        catchError(this.handleError)
      );
  }

  /**
   * Obtener usuario por ID
   */
  getUsuario(id: number): Observable<ApiResponse<Usuario>> {
    return this.http.get<ApiResponse<Usuario>>(`${this.API_BASE_URL}/usuarios/${id}`)
      .pipe(
        retry(2),
        catchError(this.handleError)
      );
  }

  // ============ IMÁGENES ============

  /**
   * Subir imagen sin usar base64
   */
  uploadImagen(file: File, reporteId: number): Observable<ApiResponse<{id: number, ruta: string}>> {
    const formData = new FormData();
    formData.append('imagen', file);
    formData.append('id_reporte', reporteId.toString());
    
    return this.http.post<ApiResponse<{id: number, ruta: string}>>(
      `${this.API_BASE_URL}/imagenes/upload`, 
      formData
    ).pipe(
      retry(2),
      catchError(this.handleError)
    );
  }

  /**
   * Subir múltiples imágenes
   */
  uploadMultiplesImagenes(files: File[], reporteId: number): Observable<ApiResponse<any[]>> {
    const formData = new FormData();
    
    files.forEach((file, index) => {
      formData.append(`imagenes`, file);
    });
    formData.append('id_reporte', reporteId.toString());
    
    return this.http.post<ApiResponse<any[]>>(
      `${this.API_BASE_URL}/imagenes/upload-multiple`, 
      formData
    ).pipe(
      retry(2),
      catchError(this.handleError)
    );
  }

  /**
   * Obtener imágenes de un reporte
   */
  getImagenesReporte(reporteId: number): Observable<ApiResponse<ImagenReporte[]>> {
    return this.http.get<ApiResponse<ImagenReporte[]>>(`${this.API_BASE_URL}/reportes/${reporteId}/imagenes`)
      .pipe(
        retry(2),
        catchError(this.handleError)
      );
  }

  /**
   * Eliminar imagen
   */
  eliminarImagen(imagenId: number, reporteId: number): Observable<ApiResponse<any>> {
    return this.http.delete<ApiResponse<any>>(`${this.API_BASE_URL}/imagenes/${imagenId}`, {
      body: { id_reporte: reporteId }
    }).pipe(
      retry(2),
      catchError(this.handleError)
    );
  }

  // ============ FIRMAS ============

  /**
   * Subir firma como archivo (sin base64)
   */
  uploadFirma(file: File, reporteId: number, tipo: 'tecnico' | 'encargado'): Observable<ApiResponse<{ruta: string}>> {
    const formData = new FormData();
    formData.append('firma', file);
    formData.append('id_reporte', reporteId.toString());
    formData.append('tipo', tipo);
    
    return this.http.post<ApiResponse<{ruta: string}>>(
      `${this.API_BASE_URL}/firmas/upload`, 
      formData
    ).pipe(
      retry(2),
      catchError(this.handleError)
    );
  }

  /**
   * Subir firma de encargado para un reporte específico
   */
  uploadFirmaEncargado(file: File, reporteId: number, datosEncargado: {nombre: string, cedula: string}): Observable<ApiResponse<{ruta: string}>> {
    const validacion = this.validarImagen(file);
    if (!validacion.valido) {
      return throwError(() => new Error(validacion.error));
    }

    const formData = new FormData();
    formData.append('firma', file);
    formData.append('id_reporte', reporteId.toString());
    formData.append('nombre_encargado', datosEncargado.nombre);
    formData.append('cedula_encargado', datosEncargado.cedula);
    
    return this.http.post<ApiResponse<{ruta: string}>>(
      `${this.API_BASE_URL}/firmas/encargado/upload`, 
      formData
    ).pipe(
      retry(2),
      catchError(this.handleError)
    );
  }

  /**
   * Obtener firma predefinida de técnico por nombre
   */
  getFirmaTecnico(nombreTecnico: string): Observable<ApiResponse<{ruta_firma: string}>> {
    return this.http.get<ApiResponse<{ruta_firma: string}>>(
      `${this.API_BASE_URL}/firmas/tecnico/${encodeURIComponent(nombreTecnico)}`
    ).pipe(
      retry(2),
      catchError(this.handleError)
    );
  }

  // ============ UTILIDADES ============

  /**
   * Validar archivo de imagen
   */
  validarImagen(file: File): {valido: boolean, error?: string} {
    const tiposPermitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    const tamañoMaximo = 5 * 1024 * 1024; // 5MB

    if (!tiposPermitidos.includes(file.type)) {
      return {
        valido: false,
        error: 'Tipo de archivo no permitido. Solo se aceptan: JPEG, JPG, PNG, GIF'
      };
    }

    if (file.size > tamañoMaximo) {
      return {
        valido: false,
        error: 'El archivo es demasiado grande. Máximo 5MB'
      };
    }

    return { valido: true };
  }

  /**
   * Generar nombre único para archivo
   */
  generarNombreUnico(nombreOriginal: string): string {
    const timestamp = Date.now();
    const random = Math.random().toString(36).substring(2, 8);
    const extension = nombreOriginal.split('.').pop();
    return `${timestamp}_${random}.${extension}`;
  }

  // ============ MÉTODOS ADICIONALES ============

  /**
   * Obtener reporte por ID (alias para compatibilidad)
   */
  obtenerReporte(id: number): Observable<ApiResponse<Reporte>> {
    return this.getReporte(id);
  }

  /**
   * Obtener imágenes de un reporte
   */
  obtenerImagenes(reporteId: number): Observable<ApiResponse<any[]>> {
    return this.http.get<ApiResponse<any[]>>(`${this.API_BASE_URL}/reportes/${reporteId}/imagenes`)
      .pipe(
        retry(2),
        catchError(this.handleError)
      );
  }

  /**
   * Manejo de errores
   */
  private handleError = (error: any): Observable<never> => {
    console.error('Error en ReportesLambdaService:', error);
    
    let errorMessage = 'Ocurrió un error inesperado';
    
    if (error.error instanceof ErrorEvent) {
      // Error del lado del cliente
      errorMessage = `Error: ${error.error.message}`;
    } else {
      // Error del lado del servidor
      errorMessage = `Error ${error.status}: ${error.error?.message || error.message}`;
    }
    
    return throwError(() => new Error(errorMessage));
  };
}
