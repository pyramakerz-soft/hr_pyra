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
    { label: 'Dashboard', icon: 'fa-regular fa-table-list', route: '/Dashboard' },
    { label: 'Sign Out', icon: 'fa-regular fa-sign-out', route: '/Login' },
  ];

  OpenMenu() {
    this.isMenuOpen = !this.isMenuOpen;
  }
}
