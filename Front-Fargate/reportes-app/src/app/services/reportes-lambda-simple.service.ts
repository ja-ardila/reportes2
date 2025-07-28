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
  fechai: string;
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
  success: boolean;
  message: string;
  data?: T;
  error?: string;
}

@Injectable({
  providedIn: 'root'
})
export class ReportesLambdaSimpleService {
  
  // URLs de las funciones Lambda específicas
  private readonly CREATE_LAMBDA_URL = 'https://c3mm7spikbcgwlowdqdcnw7awy0uxyln.lambda-url.us-east-1.on.aws';
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
  crearReporte(reporte: Reporte): Observable<ApiResponse<{id: number, numero_reporte: string}>> {
    return this.http.post<ApiResponse<{id: number, numero_reporte: string}>>(
      this.CREATE_LAMBDA_URL, 
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
      this.UPDATE_LAMBDA_URL, 
      { ...reporte, id }, 
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
    }
    
    return throwError(() => ({
      success: false,
      message: errorMessage,
      error: error
    }));
  }
}
