import { Component } from '@angular/core';
import Chart from 'chart.js/auto'; 
import { ChartsService } from '../../../Services/charts.service';

@Component({
  selector: 'app-bar-chart',
  standalone: true,
  // imports: [CommonModule,FormsModule],
  imports: [],
  templateUrl: './bar-chart.component.html',
  styleUrl: './bar-chart.component.css'
})
export class BarChartComponent {

  public chart: any;
  DataFromApi:any;
  Year:number=2024;
  constructor(public ChartServ:ChartsService){}


  ngOnInit(): void {
    this.createChart();
    this.GetDataFromApi()
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
              // borderDash: [5, 5], 
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

      console.log(d.employeeCount);

    })

  }
}
