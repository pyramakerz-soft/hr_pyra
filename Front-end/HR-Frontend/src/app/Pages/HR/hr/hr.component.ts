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
  isMenuOpen: boolean = false;

  menuItems = [
    { label: 'Dashboard', icon: 'fi fi-rr-table-rows', route: '/HR//HRDashBoard' },
    { label: 'Employee', icon: 'fas fa-home', route: '/HR/HREmployee' },
    { label: 'Roles', icon: 'fi fi-rs-chart-pie', route: '/HR/HRRole' },
    { label: 'Attendance ', icon: 'fi fi-rr-chart-simple', route: '/HR/HRAttendance' },
    { label: 'Locations', icon: 'fi fi-rr-chart-simple', route: '/HR/HRBounders' },
    { label: 'Department', icon: 'fi fi-rr-chart-simple', route: '/HR/HRDepartment' },
    { label: 'Sign Out', icon: 'fi fi-bs-sign-out-alt transform rotate-180', route: '' },
  ];

  OpenMenu() {
    this.isMenuOpen = !this.isMenuOpen;
  }

  closeMenu() {
    this.isMenuOpen = false;
  }
}
