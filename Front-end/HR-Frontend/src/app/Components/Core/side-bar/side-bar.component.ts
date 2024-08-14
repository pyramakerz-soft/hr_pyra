import { CommonModule } from '@angular/common';
import { Component } from '@angular/core';
import { RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';

@Component({
  selector: 'app-side-bar',
  standalone: true,
  imports: [RouterLink, RouterOutlet, RouterLinkActive,CommonModule],
  templateUrl: './side-bar.component.html',
  styleUrl: './side-bar.component.css'
})
export class SideBarComponent {
  menuItems = [
    { label: 'Dashboard', icon: 'fa-regular fa-table-list' ,  route: '/side' },
    { label: 'Profile', icon: 'fa-regular fa-user'  ,  route: '/empDashboard' },
    { label: 'Settings', icon: 'fa-regular fa-cog'  ,  route: '/empDashboard' },
    { label: 'Sign Out', icon: 'fa-regular fa-sign-out'  ,  route: '/empDashboard' },
  ];

  activeIndex: number | null = null;

  setActiveIndex(index: number): void {
    this.activeIndex = index;
  }
}