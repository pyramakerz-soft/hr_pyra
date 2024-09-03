import { CommonModule } from '@angular/common';
import { Component, Input, SimpleChanges } from '@angular/core';
import Chart from 'chart.js/auto';


@Component({
  selector: 'app-donut-chart',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './donut-chart.component.html',
  styleUrl: './donut-chart.component.css'
})
export class DonutChartComponent {
  @Input() Year: Number = 0;
  baseColor = '#437EF7';
  data = [300, 240, 100];
  labels = ['Segment 1', 'Segment 2', 'Segment 3'];
  colors = [this.baseColor]; 

  public chart: any;
  segments: { color: any; label: string, value: number }[] = [];

  ngOnInit(): void {
    this.createChart();
  }

  ngOnChanges(changes: SimpleChanges): void {
    if (changes['Year'] && !changes['Year'].isFirstChange()) {
      console.log(this.Year);
    }
  }

  createChart() {
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
  
    for (let i = 1; i < 6; i++) {
      this.colors.push(lightenColor(this.baseColor, i * 0.3)); 
    }

    this.segments = this.labels.map((label, index) => ({
      label,
      value: this.data[index],
      color: this.colors[index]
    }));
  
    this.chart = new Chart("DoughnutChart", {
      type: 'doughnut',
      data: {
        labels: this.labels,
        datasets: [{
          label: 'PieChart',
          data: this.data,
          backgroundColor: this.colors,
          hoverOffset: 4
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
          }
        }
      },
      plugins: [{
        id: 'custom-text-in-center',
        afterDraw: function(chart) {
          const ctx = chart.ctx;
          const chartArea = chart.chartArea;
          const centerX = (chartArea.left + chartArea.right) / 2;
          const centerY = (chartArea.top + chartArea.bottom) / 2;
          
          ctx.save();
          const additionalText = 'Total'; 
          ctx.textBaseline = 'middle';
          ctx.textAlign = 'center';
          
          ctx.fillStyle = '#5F6D7E'; 
          ctx.font = "normal 14px poppins"; 
          ctx.fillText(additionalText, centerX, centerY -11);
          ctx.fillStyle = '#272D37'; 
          ctx.font = "bold 22px poppins";
          ctx.fillText("500", centerX, centerY + 11);

          ctx.restore();
        }
      }]
    });
  }
}
