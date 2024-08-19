import { CommonModule } from '@angular/common';
import { Component } from '@angular/core';
import { RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';
import { SideBarComponent } from '../../../Components/Core/side-bar/side-bar.component';

@Component({
  selector: 'app-hr',
  standalone: true,
  imports: [SideBarComponent,CommonModule,RouterLink,RouterLinkActive,RouterOutlet],
  templateUrl: './hr.component.html',
  styleUrl: './hr.component.css'
})
export class HRComponent {
  menuItems = [
    { label: 'Dashboard', icon: 'fa-regular fa-table-list', route: '/HR//HREmployee' },
    { label: 'Employee', icon: 'fas fa-home', route: '/HR/HREmployee' },
    { label: 'Roles', icon: 'fa-regular fa-sign-out', route: '/HR//HREmployee' },
    { label: 'Atendence', icon: 'fas fa-chart-bar', route: '/HR//HREmployee' },
    { label: 'Bounders', icon: 'fas fa-chart-bar', route: '/HR//HREmployee' },
    { label: 'Sign Out', icon: 'fa fa-sign-out', route: '/HR//HREmployee' },

  ];

}
