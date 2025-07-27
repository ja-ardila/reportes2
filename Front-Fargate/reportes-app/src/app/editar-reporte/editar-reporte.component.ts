import { Component, OnInit, ViewChild, ElementRef, AfterViewInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, FormGroup, FormsModule, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router, ActivatedRoute } from '@angular/router';
import { ReportesLambdaService, Reporte } from '../services/reportes-lambda.service';
import SignaturePad from 'signature_pad';
import { HttpClient, HttpClientModule } from '@angular/common/http';

@Component({
  selector: 'app-editar-reporte',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, HttpClientModule],
  templateUrl: './editar-reporte.component.html',
  styleUrls: ['./editar-reporte.component.css']
})
export class EditarReporteComponent implements AfterViewInit {
  formulario!: FormGroup;
  firmaBase64: string = '';
  imagenesBase64: string[] = [];
  imagenesServidor: { id: number, ruta: string }[] = [];

  @ViewChild('firmaCanvas', { static: true }) firmaCanvas!: ElementRef<HTMLCanvasElement>;
  private signaturePad!: SignaturePad;
  private id!: string;

  constructor(
    private fb: FormBuilder,
    private route: ActivatedRoute,
    private http: HttpClient,
    private router: Router
  ) {}

  ngOnInit(): void {
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
      informe: ['', Validators.required],
      observaciones: ['', Validators.required],
      cedulat: ['', Validators.required],
      firma: ['', Validators.required],
      nombret: ['', Validators.required],
      cedulae: ['', Validators.required],
      nombree: ['', Validators.required],
      signature: [''],
      imagenes: [[]]
    });

    this.id = this.route.snapshot.paramMap.get('id')!;
    this.http.get<any>(`/api/reportes/${this.id}`).subscribe(data => {
      this.formulario.patchValue(data);
      this.imagenesServidor = data.imagenes || [];
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

  eliminarImagen(idImagen: number): void {
    if (confirm('¿Estás seguro de que deseas eliminar esta imagen?')) {
      this.http.post('/api/eliminar-imagen', { id_imagen: idImagen }).subscribe(() => {
        this.imagenesServidor = this.imagenesServidor.filter(img => img.id !== idImagen);
      });
    }
  }

  onSubmit(): void {
    if (!this.signaturePad.isEmpty()) {
      this.firmaBase64 = this.signaturePad.toDataURL();
      this.formulario.patchValue({ signature: this.firmaBase64 });
    }

    const payload = this.formulario.value;
    this.http.put(`/api/reportes/${this.id}`, payload).subscribe({
      next: () => {
        alert('Reporte actualizado correctamente');
        this.router.navigate(['/listado']);
      },
      error: err => alert('Error al actualizar el reporte: ' + err.message)
    });
  }
}
