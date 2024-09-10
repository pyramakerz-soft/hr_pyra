import { Component } from '@angular/core';
import { DashboardHeroComponent } from '../../../Components/dashboard-hero/dashboard-hero.component';
import { DonutChartComponent } from '../../../Components/Charts/donut-chart/donut-chart.component';
import { BarChartComponent } from '../../../Components/Charts/bar-chart/bar-chart.component';
import { CardChartComponent } from '../../../Components/Charts/card-chart/card-chart.component';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ChartsService } from '../../../Services/charts.service';

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

  userWorkTypes = {
    home: 0,
    site: 0
  };

  constructor(public chartService:ChartsService){}

  ngOnInit(){
    this.populateYears()
    const currentDate = new Date();
    this.selectedYear = currentDate.getFullYear();
    this.getDataPercentage()      
    localStorage.setItem('HrEmployeeCN', "1");
    localStorage.setItem('HrLocationsCN', "1");
    localStorage.setItem('HrAttendaceCN', "1");
    localStorage.setItem('HrAttanceDetailsCN', "1");


  }

  onYearChange(event: Event): void {
    const target = event.target as HTMLSelectElement;
    if (target) {
      this.selectedYear = +target.value
      this.getDataPercentage()
    }
  }

  populateYears(): void {
    const startYear = 2019;
    const currentYear = new Date().getFullYear(); 

    for (let year = startYear; year <= currentYear; year++) {
      this.years.push(year);
    }
  }

  getDataPercentage(){
    this.Data = [
      {
        label: 'Work From Home',
        icon: 'fi fi-rs-chart-pie',
        percentage: '0%'
      },
      {
        label: 'On Site',
        icon: 'fi fi-tr-dot-circle',
        percentage: '0%'
      }
    ];
    this.chartService.getEmployeesWorkTypesprecentage(this.selectedYear).subscribe(
      (d:any)=>{
        this.userWorkTypes = d.userWorkTypes
        
        Object.keys(this.userWorkTypes).forEach((key) => {
          const workTypeKey = key as keyof typeof this.userWorkTypes;
          this.formattedWorkTypes[key] = `${(this.userWorkTypes[workTypeKey]).toFixed(2)}%`;
        });

        this.Data = [
          {
            label: 'Work From Home',
            icon: 'fi fi-rs-chart-pie',
            percentage: this.formattedWorkTypes['home']
          },
          {
            label: 'On Site',
            icon: 'fi fi-tr-dot-circle',
            percentage: this.formattedWorkTypes['site']
          }
        ];
      }
    )
  }
}
