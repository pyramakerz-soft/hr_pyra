import { Component } from '@angular/core';
import { SideBarComponent } from '../../../Components/Core/side-bar/side-bar.component';
import { CommonModule } from '@angular/common';
import { RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';

@Component({
  selector: 'app-employee',
  standalone: true,
  imports: [SideBarComponent,CommonModule,RouterLink,RouterLinkActive,RouterOutlet],
  templateUrl: './employee.component.html',
  styleUrl: './employee.component.css'
})
export class EmployeeComponent {
  isMenuOpen: boolean = false;

  menuItems = [
    { label: 'Dashboard', icon: 'fi fi-rr-table-rows', route: '/employee/Dashboard' , notificationCount: 0 },
    { label: 'Sign Out', icon: 'fi fi-bs-sign-out-alt transform rotate-180', route: '/Login' , notificationCount: 0 },
  ];

  OpenMenu() {
    this.isMenuOpen = !this.isMenuOpen;
  }

  closeMenu() {
    this.isMenuOpen = false;
  }
}
