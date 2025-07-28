import { Routes } from '@angular/router';
import { CrearReporteComponent } from './crear-reporte/crear-reporte.component';
import { EditarReporteComponent } from './editar-reporte/editar-reporte.component';

export const routes: Routes = [
  { path: 'crear-reporte', component: CrearReporteComponent },
  { path: 'editar-reporte/:id', component: EditarReporteComponent },
];
