import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, FormGroup, FormsModule, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router, ActivatedRoute, RouterModule } from '@angular/router';
import { ReportesLambdaService, Reporte, ApiResponse } from '../services/reportes-lambda.service';
import { HttpClient, HttpClientModule } from '@angular/common/http';

@Component({
  selector: 'app-editar-reporte',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, HttpClientModule, RouterModule],
  templateUrl: './editar-reporte.component.html',
  styleUrls: ['./editar-reporte.component.css']
})
export class EditarReporteComponent implements OnInit {
  formulario!: FormGroup;
  imagenesBase64: string[] = [];
  imagenesServidor: { id: number, ruta: string }[] = [];
  reporteId: string = '';
  tecnicos = [
    { id: 'firmaDB.png', nombre: 'Daniel Botía' },
    { id: 'firmaDG.png', nombre: 'Diego Garzón' },
    { id: 'firmaJA.png', nombre: 'Jorge Ardila' },
    { id: 'firmaMF.png', nombre: 'Miguel Forero' }
  ];

  private id!: string;

  constructor(
    private fb: FormBuilder,
    private route: ActivatedRoute,
    private http: HttpClient,
    private router: Router,
    private reportesService: ReportesLambdaService
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
      fecha_inicio: ['', Validators.required],
      fecha_cierre: ['', Validators.required],
      hora_inicio: ['', Validators.required],
      hora_cierre: ['', Validators.required],
      servicior: ['', Validators.required],
      tiposervicio: ['', Validators.required],
      informe: ['', Validators.required],
      observaciones: ['', Validators.required],
      cedulat: ['', Validators.required],
      firma: ['', Validators.required],
      nombret: ['', Validators.required],
      cedulae: ['', Validators.required],
      nombree: ['', Validators.required],
      imagenes: [[]]
    });

    this.id = this.route.snapshot.paramMap.get('id')!;
    this.reporteId = this.id; // Para mostrar en el título
    this.http.get<any>(`/api/reportes/${this.id}`).subscribe(data => {
      this.formulario.patchValue(data);
      this.imagenesServidor = data.imagenes || [];
    });
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
    if (this.formulario.valid) {
      const reporteData: Reporte = {
        ...this.formulario.value,
        usuario: 'usuario_sistema' // Puedes cambiar esto por el usuario actual
      };

      this.reportesService.actualizarReporte(parseInt(this.id), reporteData).subscribe({
        next: (response: ApiResponse<any>) => {
          // La respuesta del Lambda viene con message y reporte
          if (response.message === 'Reporte actualizado exitosamente' || response.reporte) {
            alert('Reporte actualizado correctamente');
            this.router.navigate(['/listado']);
          } else {
            alert('Error al actualizar el reporte: ' + response.message);
          }
        },
        error: (err: any) => {
          console.error('Error:', err);
          alert('Error al actualizar el reporte. Verifique su conexión.');
        }
      });
    } else {
      alert('Por favor complete todos los campos requeridos');
    }
  }
}
