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

  segments = [
    { label: 'January', value: 30, color: '#437EF7' },
    { label: 'February', value: 50, color: '#437EF7' },
    { label: 'March', value: 70, color: '#437EF7' },
    { label: 'April', value: 40, color: '#437EF7' },
    { label: 'May', value: 60, color: '#437EF7' },
    { label: 'June', value: 20, color: '#437EF7' },
    { label: 'July', value: 80, color: '#437EF7' },
    { label: 'August', value: 55, color: '#437EF7' },
    { label: 'September', value: 45, color: '#437EF7' },
    { label: 'October', value: 65, color: '#437EF7' },
    { label: 'November', value: 35, color: '#437EF7' },
    { label: 'December', value: 75, color: '#437EF7' }
  ];

  // Initialize normalizedSegments
  normalizedSegments:any[] = [];

  ngOnInit() {
    // Calculate max segment value
    const maxSegmentValue = Math.max(...this.segments.map(segment => segment.value));

    // Normalize segment values
    this.normalizedSegments = this.segments.map(segment => ({
      ...segment,
      normalizedValue: (segment.value / maxSegmentValue) * 60  // Normalize to range 1-60
    }));
  }
}
