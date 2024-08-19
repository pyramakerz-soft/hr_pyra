import { CommonModule } from '@angular/common';
import { Component } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { SideBarComponent } from '../../../Components/Core/side-bar/side-bar.component';

@Component({
  selector: 'app-hrdashboard',
  standalone: true,
  imports: [CommonModule,FormsModule,SideBarComponent],
  templateUrl: './hrdashboard.component.html',
  styleUrl: './hrdashboard.component.css'
})
export class HRDashboardComponent {
}
