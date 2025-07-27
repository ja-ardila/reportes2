import { Component, OnInit, ViewChild, ElementRef, AfterViewInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, FormGroup, FormsModule, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { HttpClient, HttpClientModule } from '@angular/common/http';
import { ReportesLambdaService, Reporte, Usuario, ApiResponse } from '../services/reportes-lambda.service';

import SignaturePad from 'signature_pad';

@Component({
  selector: 'app-crear-reporte',
  standalone: true,
 imports: [CommonModule, ReactiveFormsModule, HttpClientModule],
  templateUrl: './crear-reporte.component.html',
  styleUrls: ['./crear-reporte.component.css']
})
export class CrearReporteComponent implements AfterViewInit {
  formulario: FormGroup;
  imagenesBase64: string[] = [];
  firmaBase64: string = '';

  @ViewChild('firmaCanvas', { static: true }) firmaCanvas!: ElementRef<HTMLCanvasElement>;
  private signaturePad!: SignaturePad;

  constructor(private fb: FormBuilder, private http: HttpClient) {
    this.formulario = this.fb.group({
      empresa: ['', Validators.required],
      nit: ['', Validators.required],
      direccion: ['', Validators.required],
      telefono: ['', Validators.required],
      contacto: ['', Validators.required],
      email: ['', [Validators.required, Validators.email]],
      ciudad: ['', Validators.required],
      fechai: ['', Validators.required],
      fechac: ['', Validators.required],
      horai: ['', Validators.required],
      horac: ['', Validators.required],
      servicior: ['', Validators.required],
      tiposervicio: ['', Validators.required],
      informe: ['', [Validators.required, Validators.maxLength(4500)]],
      observaciones: ['', [Validators.required, Validators.maxLength(3000)]],
      cedulat: ['', Validators.required],
      firma: ['', Validators.required],
      nombret: ['', Validators.required],
      cedulae: ['', Validators.required],
      nombree: ['', Validators.required],
      signature: [''],
      imagenes: [[]]
    });
  }

  ngAfterViewInit(): void {
    this.signaturePad = new SignaturePad(this.firmaCanvas.nativeElement);
    this.resizeCanvas();
  }

  resizeCanvas(): void {
    const canvas = this.firmaCanvas.nativeElement;
    const ratio = Math.max(window.devicePixelRatio || 1, 1);
    canvas.width = canvas.offsetWidth * ratio;
    canvas.height = canvas.offsetHeight * ratio;
    canvas.getContext('2d')?.scale(ratio, ratio);
    this.signaturePad.clear();
  }

  clearSignature(): void {
    this.signaturePad.clear();
  }

  onFileChange(event: Event): void {
    const input = event.target as HTMLInputElement;
    if (input.files) {
      const files = Array.from(input.files);
      const promises = files.map(file => this.convertFileToBase64(file));
      Promise.all(promises).then(base64Images => {
        this.imagenesBase64 = base64Images;
        this.formulario.patchValue({ imagenes: base64Images });
      });
    }
  }

  private convertFileToBase64(file: File): Promise<string> {
    return new Promise((resolve, reject) => {
      const reader = new FileReader();
      reader.readAsDataURL(file);
      reader.onload = () => resolve(reader.result as string);
      reader.onerror = error => reject(error);
    });
  }

  onSubmit(): void {
    if (!this.signaturePad.isEmpty()) {
      this.firmaBase64 = this.signaturePad.toDataURL();
      this.formulario.patchValue({ signature: this.firmaBase64 });
    }

    const payload = this.formulario.value;
    // Aquí se envía como JSON en vez de FormData
    this.http.post('URL_DEL_BACKEND.php', payload).subscribe({
      next: () => alert('Reporte enviado correctamente'),
      error: (err) => alert('Error al enviar el reporte: ' + err.message)
    });
  }
}
