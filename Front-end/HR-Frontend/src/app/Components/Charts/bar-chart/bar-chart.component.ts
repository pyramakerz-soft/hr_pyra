import { Component } from '@angular/core';
import Chart from 'chart.js/auto'; 

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

  ngOnInit(): void {
    this.createChart();
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

  // segments = [
  //   { label: 'January', value: 30, color: '#437EF7' },
  //   { label: 'February', value: 50, color: '#437EF7' },
  //   { label: 'March', value: 70, color: '#437EF7' },
  //   { label: 'April', value: 40, color: '#437EF7' },
  //   { label: 'May', value: 60, color: '#437EF7' },
  //   { label: 'June', value: 20, color: '#437EF7' },
  //   { label: 'July', value: 80, color: '#437EF7' },
  //   { label: 'August', value: 55, color: '#437EF7' },
  //   { label: 'September', value: 45, color: '#437EF7' },
  //   { label: 'October', value: 65, color: '#437EF7' },
  //   { label: 'November', value: 35, color: '#437EF7' },
  //   { label: 'December', value: 75, color: '#437EF7' }
  // ];

  // // Initialize normalizedSegments
  // normalizedSegments:any[] = [];

  // ngOnInit() {
  //   // Calculate max segment value
  //   const maxSegmentValue = Math.max(...this.segments.map(segment => segment.value));

  //   // Normalize segment values
  //   this.normalizedSegments = this.segments.map(segment => ({
  //     ...segment,
  //     normalizedValue: (segment.value / maxSegmentValue) * 60  // Normalize to range 1-60
  //   }));
  // }
}
