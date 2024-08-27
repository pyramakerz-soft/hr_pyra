import { CommonModule } from '@angular/common';
import { Component, Input } from '@angular/core';

@Component({
  selector: 'app-donut-chart',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './donut-chart.component.html',
  styleUrl: './donut-chart.component.css'
})
export class DonutChartComponent {
  @Input() segments: { label: string; value: number; color: string }[] = [
    { label: 'Software', value: 30, color: '#437EF7' },
    { label: 'Marketing', value: 20, color: '#135DCB' },
    { label: 'Academy', value: 50, color: '#5CB1FF' }
  ];

  strokeDasharrays: string[] = [];
  strokeOffsets: number[] = [];
  totalValue: number = 0;

  ngOnInit(): void {
    this.calculateSegments();
  }

  calculateSegments(): void {
    this.totalValue = this.segments.reduce((sum, segment) => sum + segment.value, 0);
    let cumulativePercentage = 0;

    this.strokeDasharrays = this.segments.map(segment => {
      const dasharrayValue = (segment.value / this.totalValue) * 100;
      const dasharray = `${dasharrayValue} ${100 - dasharrayValue}`;
      const strokeOffset = cumulativePercentage;
      cumulativePercentage += dasharrayValue;
      this.strokeOffsets.push(strokeOffset);
      return dasharray;
    });
  }
}
