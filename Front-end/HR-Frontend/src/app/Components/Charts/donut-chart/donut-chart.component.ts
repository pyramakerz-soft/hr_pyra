import { CommonModule } from '@angular/common';
import { Component, Input, SimpleChanges } from '@angular/core';
import Chart from 'chart.js/auto';
import { ChartsService } from '../../../Services/charts.service';


@Component({
  selector: 'app-donut-chart',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './donut-chart.component.html',
  styleUrl: './donut-chart.component.css'
})
export class DonutChartComponent {
  @Input() Year: Number = 0;
  baseColor = '#135DCB';
  data:any = [];
  labels:any = [];
  colors = [this.baseColor]; 
  total = 0

  public chart: any;
  segments: { color: any; label: string, value: number }[] = [];

  constructor(public chartService:ChartsService){}

  ngOnInit() {
    this.getData();
  }

  ngOnChanges(changes: SimpleChanges): void {
    if (changes['Year'] && !changes['Year'].isFirstChange()) {
      this.getData()
    }
  }

  ngOnDestroy() {
    if (this.chart) {
      this.chart.destroy();
    }
  }

  createChart() {
    if (this.chart) {
      this.chart.destroy();
    }

    function lightenColor(color: string, percent: number) {
      color = color.replace(/^#/, '');
  
      let r = parseInt(color.substring(0, 2), 16);
      let g = parseInt(color.substring(2, 4), 16);
      let b = parseInt(color.substring(4, 6), 16);
  
      r = Math.min(255, Math.floor(r + (255 - r) * percent));
      g = Math.min(255, Math.floor(g + (255 - g) * percent));
      b = Math.min(255, Math.floor(b + (255 - b) * percent));
  
      return `#${[r, g, b].map(x => x.toString(16).padStart(2, '0')).join('').toUpperCase()}`;
    }
  
    for (let i = 1; i <= this.data.length; i++) {
      this.colors.push(lightenColor(this.baseColor, i * 0.22)); 
      this.total = this.total + this.data[i - 1]
    }

    this.segments = this.labels.map((label: any, index: number ) => ({
      label,
      value: this.data[index],
      color: this.colors[index]
    }));
  
    this.chart = new Chart("DoughnutChart", {
      type: 'doughnut',
      data: {
        labels: this.labels,
        datasets: [{
          label: 'Count',
          data: this.data,
          backgroundColor: this.colors,
          hoverOffset: 10
        }],
      },
      options: {
        aspectRatio: 2.5,
        elements: {
          arc: {
            borderWidth: 0
          }
        },
        cutout: '80%',
        plugins:{
          legend: {
            display: false
          },
          tooltip: {
            backgroundColor: '#000000', 
            titleColor: '#fff', 
            bodyColor: '#fff', 
            borderColor: '#fff', 
            borderWidth: 1, 
            padding: 10,
          }
        }
      },
      plugins: [{
        id: 'custom-text-in-center',
        beforeDraw: (chart) => { 
          const ctx = chart.ctx;
          const chartArea = chart.chartArea;
          const centerX = (chartArea.left + chartArea.right) / 2;
          const centerY = (chartArea.top + chartArea.bottom) / 2;
          
          ctx.save();
          const additionalText = 'Total'; 
          const totalNum = (this.total).toString(); 
          ctx.textBaseline = 'middle';
          ctx.textAlign = 'center';
          
          ctx.fillStyle = '#5F6D7E'; 
          ctx.font = "normal 14px poppins"; 
          ctx.fillText(additionalText, centerX, centerY -11);
          ctx.fillStyle = '#272D37'; 
          ctx.font = "bold 22px poppins";
          ctx.fillText(totalNum, centerX, centerY + 11);

          ctx.restore();
        }
      }]
    });
  }

  getData(){
    this.chartService.getDepartmentEmployees(this.Year).subscribe(
      (d:any)=>{
        this.data = []
        this.labels = []
        this.colors = [this.baseColor];
        this.total = 0

        Object.keys(d.departmentEmployeesCounts).forEach((key) => {
          this.data.push(d.departmentEmployeesCounts[key])
          this.labels.push(key)
        });

        this.createChart();
      },
      (error) => {
        if(error.error.status == 404){
          this.data = []
          this.colors = [];
          this.total = 0
          this.createChart();
        }
      }
    )
  }
}
