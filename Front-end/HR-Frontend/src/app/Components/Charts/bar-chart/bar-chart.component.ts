import { Component, Input, SimpleChanges } from '@angular/core';
import Chart from 'chart.js/auto'; 
import { ChartsService } from '../../../Services/charts.service';

@Component({
  selector: 'app-bar-chart',
  standalone: true,
  imports: [],
  templateUrl: './bar-chart.component.html',
  styleUrl: './bar-chart.component.css'
})
export class BarChartComponent {
  @Input() Year: Number = 0;

  public chart: any;
  DataFromApi:any;
  constructor(public ChartServ:ChartsService){}

  ngOnInit(): void {
    this.createChart();
    this.GetDataFromApi()
  }

  ngOnChanges(changes: SimpleChanges): void {
    if (changes['Year'] && !changes['Year'].isFirstChange()) {
      this.GetDataFromApi()
    }
  }

  createChart(){
    this.chart = new Chart("EmployeesPerMonth", {
      type: 'bar',
      data: {
        labels: ['Jan', 'Feb', 'Mar','Apr', 'May', 'Jun', 'Jul','Aug', 'Sep', 'Oct', 'Nov', 'Dec'], 
	       datasets: [
          {
            label: "Count",
            data: ['30','50', '70', '40', '60', '20', '80', '55', '45', '65', '35', '75'],
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

      console.log(d.employeeCount["2024-Apr"]);
      Object.keys(d.employeeCount).forEach((item) => {
        console.log(d.employeeCount[item].employee_count)
      })

    })

  }
}




