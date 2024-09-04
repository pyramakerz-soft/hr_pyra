import { Component, Input, SimpleChanges } from '@angular/core';
import Chart from 'chart.js/auto'; 
import { ChartsService } from '../../../Services/charts.service';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-bar-chart',
  standalone: true,
  imports: [CommonModule,FormsModule],
  templateUrl: './bar-chart.component.html',
  styleUrl: './bar-chart.component.css'
})
export class BarChartComponent {
  @Input() Year: Number = 0;

  public chart: Chart | undefined ;
  DataFromApi:number[]=[];
  flag=false
  
  constructor(public ChartServ:ChartsService){}

  ngOnInit() {
    this.GetDataFromApi();

  }

  ngOnChanges(changes: SimpleChanges): void {
    if (changes['Year'] && !changes['Year'].isFirstChange()) {
      this.GetDataFromApi()
    }
  }

  createChart(){
    if (this.chart) {
      this.chart.destroy();
    }
    this.chart = new Chart("EmployeesPerMonth", {
      type: 'bar',
      data: {
        labels: ['Jan', 'Feb', 'Mar','Apr', 'May', 'Jun', 'Jul','Aug', 'Sep', 'Oct', 'Nov', 'Dec'], 
	       datasets: [
          {
            label: "Count",
            data: this.DataFromApi,
            backgroundColor: '#437EF7',
            borderRadius: 5
          }, 
        ],
      },
      options: {
        responsive: true,
        aspectRatio: 2.7,
        plugins: {
          legend: {
            display: false,
          }
        },
  
        scales: {
          x: {
            grid: {
              display: false 
            },
            border: {
              color: 'transparent', 
              width: 0 
            }
          },
          y: {
            
            grid: {
              display: true,
              color: 'rgb(223,221,221,0.32)', 
            },
            border: {
              color: 'transparent', 
              width: 0  
            },
          }
        }
      }
    });
  }


  GetDataFromApi(){
    this.ChartServ.GetEmployeePerMonth(this.Year).subscribe((d:any)=>{
      this.DataFromApi=[]

      Object.keys(d.employeeCount).forEach((item) => {
        this.DataFromApi.push(d.employeeCount[item].employee_count)
      })
    this.createChart();
      
    })

  }

}



