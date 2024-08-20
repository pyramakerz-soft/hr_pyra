import { Component } from '@angular/core';
import { SideBarComponent } from '../../Components/Core/side-bar/side-bar.component';
import { TableComponent } from '../../Components/Core/table/table.component';
import { ClockInComponent } from '../../Components/clock-in/clock-in.component';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-employee-dashboard',
  standalone: true,
  imports: [SideBarComponent, TableComponent, ClockInComponent, CommonModule],
  templateUrl: './employee-dashboard.component.html',
  styleUrl: './employee-dashboard.component.css'
})
export class EmployeeDashboardComponent {
}
