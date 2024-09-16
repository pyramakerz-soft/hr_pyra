import { CommonModule } from '@angular/common';
import { Component, Input, SimpleChanges } from '@angular/core';

@Component({
  selector: 'app-card-chart',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './card-chart.component.html',
  styleUrl: './card-chart.component.css'
})
export class CardChartComponent {
  @Input() Data: { label: string; icon: string; percentage: string;} = {
    label: '',
    icon: '',
    percentage: ''
  };

  ngOnChanges(changes: SimpleChanges): void {
    if (changes['Data'] && !changes['Data'].isFirstChange()) {
    }
  }
}
