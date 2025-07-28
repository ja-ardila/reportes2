import { Component, OnInit, ViewChild, ElementRef, AfterViewInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, FormGroup, FormsModule, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { HttpClient, HttpClientModule } from '@angular/common/http';
import { ReportesLambdaService, Reporte, Usuario, ApiResponse } from '../services/reportes-lambda.service';
import { v4 as uuidv4 } from 'uuid';
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
  tecnicos: Usuario[] = [
    { id: 1, nombre: 'Técnico 1', rol: 'tecnico', firma_path: '' },
    { id: 2, nombre: 'Técnico 2', rol: 'tecnico', firma_path: '' },
    { id: 3, nombre: 'Técnico 3', rol: 'tecnico', firma_path: '' }
  ];
  @ViewChild('firmaCanvas', { static: true }) firmaCanvas!: ElementRef<HTMLCanvasElement>;
  private signaturePad!: SignaturePad;

  constructor(
    private fb: FormBuilder, 
    private http: HttpClient,
    private reportesService: ReportesLambdaService
  ) {
    this.formulario = this.fb.group({
      empresa: ['', Validators.required],
      nit: ['', Validators.required],
      direccion: ['', Validators.required],
      telefono: ['', Validators.required],
      contacto: ['', Validators.required],
      email: ['', [Validators.required, Validators.email]],
      ciudad: ['', Validators.required],
      fecha_inicio: ['', Validators.required],
      fechac: ['', Validators.required],
      horai: ['', Validators.required],
      horac: ['', Validators.required],
      servicio_reportado: ['', Validators.required],
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

    if (this.formulario.valid) {
      const reporte: Reporte = {
        ...this.formulario.value,
        numero_reporte: uuidv4(),
        usuario: 'usuario_sistema' // Puedes cambiar esto por el usuario actual
      };

      this.reportesService.crearReporte(reporte).subscribe({
        next: (response) => {
          debugger;
          // La respuesta del Lambda viene con message, reporte y reporteId
          if (response.message === 'Reporte creado exitosamente' || response.reporte) {
            const reporteCreado = response.reporte;
            const numeroReporte = reporteCreado?.numero_reporte || response.reporteId;
            alert(`Reporte creado correctamente. ID: ${reporteCreado?.id}, Número: ${numeroReporte}`);
            this.formulario.reset();
            this.signaturePad.clear();
            this.imagenesBase64 = [];
          } else {
            alert('Error al crear el reporte: ' + response.message);
          }
        },
        error: (err) => {
          console.error('Error:', err);
          alert('Error al enviar el reporte. Verifique su conexión.');
        }
      });
    } else {
      alert('Por favor complete todos los campos requeridos');
    }
  }
}
