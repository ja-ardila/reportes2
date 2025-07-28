// Configuración temporal para las URLs de Lambda
// Solo implementamos crear y actualizar reporte por ahora

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
  fecha_inicio: string;
  fechac: string;
  horai: string;
  horac: string;
  servicior: string;
  tiposervicio: string;
  informe: string;
  observaciones: string;
  cedulat: string;
  nombret: string;
  firma: string;
  cedulae: string;
  nombree: string;
  signature?: string;
  fecha: string;
  usuario: string;
}

export interface Usuario {
  id: number;
  nombre: string;
  rol: string;
  firma_path?: string;
}

export interface ApiResponse<T> {
  success?: boolean;
  message: string;
  data?: T;
  error?: string;
  reporte?: any;
  reporteId?: number;
}

@Injectable({
  providedIn: 'root'
})
export class ReportesLambdaService {
  
  // URLs de las funciones Lambda específicas
  private readonly CREATE_LAMBDA_URL = 'https://wn4g2hmfaenzk465fhrg2fxdd40gyzda.lambda-url.us-east-1.on.aws/';
  private readonly UPDATE_LAMBDA_URL = 'https://qbmwn3v4cyvapv2zqwwrufipye0avpie.lambda-url.us-east-1.on.aws';
  
  private httpOptions = {
    headers: new HttpHeaders({
      'Content-Type': 'application/json'
    })
  };

  constructor(private http: HttpClient) { }

  /**
   * Crear nuevo reporte
   */
  crearReporte(reporte: Reporte): Observable<any> {
    debugger;
  
     return this.http.post<ApiResponse<any>>(`${this.CREATE_LAMBDA_URL}`, reporte);
  }

  /**
   * Actualizar reporte existente
   */
  actualizarReporte(id: number, reporte: Reporte): Observable<ApiResponse<any>> {
    // Crear una copia del reporte sin el id para evitar el error de campo prohibido
    const { id: reporteId, ...reporteData } = reporte;
    
    return this.http.put<ApiResponse<any>>(
      `${this.UPDATE_LAMBDA_URL}?id=${id}`, 
      reporteData, 
      this.httpOptions
    ).pipe(
      retry(2),
      catchError(this.handleError)
    );

    
  }

  /**
   * Manejo de errores HTTP
   */
  private handleError(error: any) {
    console.error('Error en la API Lambda:', error);
    
    let errorMessage = 'Error desconocido';
    if (error.error instanceof ErrorEvent) {
      // Error del cliente
      errorMessage = error.error.message;
    } else {
      // Error del servidor
      errorMessage = `Error ${error.status}: ${error.message}`;
      if (error.error && error.error.message) {
        errorMessage = error.error.message;
      }
      // Si el error viene en formato de string del Lambda
      if (typeof error.error === 'string') {
        try {
          const parsedError = JSON.parse(error.error);
          if (parsedError.error) {
            errorMessage = parsedError.error;
          }
        } catch (e) {
          errorMessage = error.error;
        }
      }
    }
    
    return throwError(() => ({
      success: false,
      message: errorMessage,
      error: error
    }));
  }
}
