import { Component } from '@angular/core';
import { CarouselComponent, CarouselControlComponent, CarouselIndicatorsComponent, CarouselInnerComponent, CarouselItemComponent, ThemeDirective } from '@coreui/angular';
import { RouterLink } from '@angular/router';
import { CommonModule } from '@angular/common';
import { CarouselModule } from '@coreui/angular';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [
    CarouselComponent,
    CarouselControlComponent,
    CarouselIndicatorsComponent,
    CarouselInnerComponent,
    CarouselItemComponent,
    ThemeDirective,
    RouterLink,
    CommonModule,
    CarouselModule
  ],
  templateUrl: './login.component.html',
  styleUrl: './login.component.css'
})
export class LoginComponent {
  // slides: any[] = new Array(3).fill({ id: -1, src: '', title: '', subtitle: '' });

  // ngOnInit(): void {
  //   this.slides[0] = {
  //     src: '../../../assets/Layer 2.png'
  //   };
  //   this.slides[1] = {
  //     src: 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcREoRGyXmHy_6aIgXYqWHdOT3KjfmnuSyxypw&s'
  //   };
  //   this.slides[2] = {
  //     src: '../../../assets/Paragraph container.png'
  //   };
  // }
}
