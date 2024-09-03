import { Component } from '@angular/core';
import { DashboardHeroComponent } from '../../../Components/dashboard-hero/dashboard-hero.component';
import { DonutChartComponent } from '../../../Components/Charts/donut-chart/donut-chart.component';
import { BarChartComponent } from '../../../Components/Charts/bar-chart/bar-chart.component';
import { CardChartComponent } from '../../../Components/Charts/card-chart/card-chart.component';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-hr-dashboard',
  standalone: true,
  imports: [DashboardHeroComponent,DonutChartComponent,BarChartComponent, CardChartComponent, CommonModule, FormsModule],
  templateUrl: './hr-dashboard.component.html',
  styleUrl: './hr-dashboard.component.css'
})
export class HrDashboardComponent {
  selectedYear: number = 0;

  Data = [
    { label: 'Work From Home', icon: 'fi fi-rs-chart-pie', percentage: ''},
    { label: 'On Site', icon: 'fi fi-tr-dot-circle', percentage: ''},
  ];

  years: number[] = [];

  formattedWorkTypes: { [key: string]: string } = {};

  ngOnInit(){
    this.populateYears()
    const currentDate = new Date();
    this.selectedYear = currentDate.getFullYear();
    this.getDataPercentage()      
  }

  onYearChange(event: Event): void {
    const target = event.target as HTMLSelectElement;
    if (target) {
      this.selectedYear = +target.value; 
      console.log(this.selectedYear)
    }
  }

  populateYears(): void {
    const startYear = 2019;
    const currentYear = new Date().getFullYear(); 

    for (let year = startYear; year <= currentYear; year++) {
      this.years.push(year);
    }
  }

  // API return ex:
  userWorkTypes = {
    site: 66.66666666666666,
    home: 33.33333333333333,
  };
  
  getDataPercentage(){
    Object.keys(this.userWorkTypes).forEach((key) => {
      const workTypeKey = key as keyof typeof this.userWorkTypes;
      this.formattedWorkTypes[key] = `${this.userWorkTypes[workTypeKey].toFixed(2)}%`;
    });

    this.Data.forEach((item) => {
      if (item.label === 'Work From Home') {
        item.percentage = this.formattedWorkTypes['home'];
      } else if (item.label === 'On Site') {
        item.percentage = this.formattedWorkTypes['site'];
      };
    });
  }
}
