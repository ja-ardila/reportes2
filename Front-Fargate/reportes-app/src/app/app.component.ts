import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterOutlet, RouterModule } from '@angular/router';

@Component({
  selector: 'app-root',
  standalone: true,
  imports: [CommonModule, RouterOutlet, RouterModule],
  template: `<router-outlet></router-outlet>`,
  styles: [`
    .app {
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    .app-header {
      background: linear-gradient(135deg, #007bff, #0056b3);
      color: white;
      padding: 1rem 0;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .header-content {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .header-content h1 {
      margin: 0;
      font-size: 1.8em;
      font-weight: 300;
    }

    .nav-menu {
      display: flex;
      gap: 2rem;
    }

    .nav-menu a {
      color: white;
      text-decoration: none;
      padding: 0.5rem 1rem;
      border-radius: 4px;
      transition: background-color 0.3s ease;
    }

    .nav-menu a:hover {
      background-color: rgba(255,255,255,0.1);
    }

    .nav-menu a.active {
      background-color: rgba(255,255,255,0.2);
      font-weight: bold;
    }

    .app-main {
      flex: 1;
      background-color: #f8f9fa;
      min-height: calc(100vh - 140px);
    }

    .app-footer {
      background-color: #343a40;
      color: white;
      text-align: center;
      padding: 1rem;
      margin-top: auto;
    }

    .app-footer p {
      margin: 0;
      font-size: 0.9em;
    }

    @media (max-width: 768px) {
      .header-content {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
      }
      
      .nav-menu {
        flex-direction: column;
        gap: 0.5rem;
        width: 100%;
      }
      
      .nav-menu a {
        text-align: center;
        display: block;
      }
    }
  `]
})
export class AppComponent {
  title = 'Sistema de Reportes H323';
}
