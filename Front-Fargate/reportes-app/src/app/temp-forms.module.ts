// Archivo temporal para manejar FormsModule hasta que se solucione el problema
import { NgModule } from '@angular/core';

// Este es un workaround temporal para FormsModule
export { FormsModule, ReactiveFormsModule } from '@angular/forms';

@NgModule({})
export class TemporaryFormsModule {}
