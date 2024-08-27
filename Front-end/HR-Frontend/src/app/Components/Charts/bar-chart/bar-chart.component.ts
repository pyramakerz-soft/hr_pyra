import { CommonModule } from '@angular/common';
import { Component } from '@angular/core';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-bar-chart',
  standalone: true,
  imports: [CommonModule,FormsModule],
  templateUrl: './bar-chart.component.html',
  styleUrl: './bar-chart.component.css'
})
export class BarChartComponent {

  monthlyData = [
    { month: 'Jan', value: 10, type: 'solid' },
    { month: 'Feb', value: 20, type: 'solid' },
    { month: 'Mar', value: 20, type: 'hatched' },
    { month: 'Apr', value: 30, type: 'solid' },
    { month: 'May', value: 50, type: 'hatched' },
    { month: 'Jun', value: 20, type: 'solid' },
    { month: 'Jul', value: 10, type: 'hatched' },
    { month: 'Aug', value: 40, type: 'solid' },
    { month: 'Sep', value: 50, type: 'solid' },
    { month: 'Oct', value: 60, type: 'solid' },
    { month: 'Nov', value: 70, type: 'solid' },
    { month: 'Dec', value: 100, type: 'solid' },
  ];
}
