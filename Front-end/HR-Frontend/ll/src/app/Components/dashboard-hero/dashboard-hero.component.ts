import { Component, Input } from '@angular/core';
import { ClockInComponent } from '../clock-in/clock-in.component';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-dashboard-hero',
  standalone: true,
  imports: [ClockInComponent,CommonModule,FormsModule],
  templateUrl: './dashboard-hero.component.html',
  styleUrl: './dashboard-hero.component.css'
})
export class DashboardHeroComponent {
}
