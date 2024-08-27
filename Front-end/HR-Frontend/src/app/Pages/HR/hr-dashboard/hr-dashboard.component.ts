import { Component } from '@angular/core';
import { DashboardHeroComponent } from '../../../Components/dashboard-hero/dashboard-hero.component';
import { DonutChartComponent } from '../../../Components/Charts/donut-chart/donut-chart.component';
import { BarChartComponent } from '../../../Components/Charts/bar-chart/bar-chart.component';
import { CardChartComponent } from '../../../Components/Charts/card-chart/card-chart.component';

@Component({
  selector: 'app-hr-dashboard',
  standalone: true,
  imports: [DashboardHeroComponent,DonutChartComponent,BarChartComponent, CardChartComponent],
  templateUrl: './hr-dashboard.component.html',
  styleUrl: './hr-dashboard.component.css'
})
export class HrDashboardComponent {
  data = [
    { label: 'Segment 1', value: 30, color: '#FF6384' },
    { label: 'Segment 2', value: 50, color: '#36A2EB' },
    { label: 'Segment 3', value: 20, color: '#FFCE56' }
  ];

  Data = [
    { label: 'Work From Home', icon: 'fi fi-rs-chart-pie', percentage: '24.7%' },
    { label: 'On Site', icon: 'fi fi-tr-dot-circle', percentage: '24.7%' },
  ];
}
