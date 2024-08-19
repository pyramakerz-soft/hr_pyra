import { Component } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { SideBarComponent } from '../../../Components/Core/side-bar/side-bar.component';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-hremployee',
  standalone: true,
  imports: [CommonModule,FormsModule,SideBarComponent],
  templateUrl: './hremployee.component.html',
  styleUrl: './hremployee.component.css'
})
export class HREmployeeComponent {
  tableData = [
    { day: 1, date: '2024-08-13', clockIn: '09:00', clockOut: '17:00', totalHours: 8, locationIn: 'Office', locationOut: 'Home' },
    { day: 2, date: '2024-08-14', clockIn: '09:00', clockOut: '17:00', totalHours: 8, locationIn: 'Office', locationOut: 'Home' },
    { day: 3, date: '2024-08-15', clockIn: '09:00', clockOut: '17:00', totalHours: 8, locationIn: 'Ofghfyffice', locationOut: '42 Abd Al Aziz Agamea, Sidi Gaber, Alexandria Governorate 5433112,Egypty' },
    { day: 4, date: '2024-08-16', clockIn: '09:00', clockOut: '', totalHours: 8, locationIn: '42 Abd Al Aziz Agamea, Sidi Gaber, Alexandria Governorate 5433112,Egypt', locationOut: '' },
    // Add more data as needed
  ];
  isMenuOpen: boolean = false;

  OpenMenu() {
    this.isMenuOpen = !this.isMenuOpen;
  }
}
