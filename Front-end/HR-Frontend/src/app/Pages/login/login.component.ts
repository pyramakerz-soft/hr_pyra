import { Component } from '@angular/core';
import { Router } from '@angular/router';
// import { CarouselComponent, CarouselControlComponent, CarouselIndicatorsComponent, CarouselInnerComponent, CarouselItemComponent, ThemeDirective } from '@coreui/angular';
// import { RouterLink } from '@angular/router';
// import { CarouselModule } from '@coreui/angular';

// import { CommonModule } from '@angular/common';
// import { CarouselModule } from 'ngx-owl-carousel-o';
// import { BrowserAnimationsModule } from '@angular/platform-browser/animations';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [
    // CarouselComponent,
    // CarouselControlComponent,
    // CarouselIndicatorsComponent,
    // CarouselInnerComponent,
    // CarouselItemComponent,
    // ThemeDirective,
    // RouterLink,
    // CommonModule, 
    // CarouselModule,
    // BrowserAnimationsModule
  ],
  templateUrl: './login.component.html',
  styleUrl: './login.component.css'
})
export class LoginComponent {
  constructor(private router: Router){  }
  SignIn(){
    this.router.navigateByUrl("/empDashboard");
  }
  // slides: any[] = new Array(3).fill({ id: -1, src: '', title: '', subtitle: '' });

  // ngOnInit(): void {
  //   this.slides[0] = {
  //     src: '../../../assets/Layer 2.png'
  //   };
  //   this.slides[1] = {
  //     src: 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcREoRGyXmHy_6aIgXYqWHdOT3KjfmnuSyxypw&s'
  //   };
  //   this.slides[2] = {
  //     src: '../../../assets/Paragraphcontainer.png'
  //   };
  // }

  // customOptions: any = {
  //   loop: true,
  //   mouseDrag: true,
  //   touchDrag: true,
  //   pullDrag: true,
  //   dots: true,
  //   navSpeed: 700,
  //   navText: ['&#8249;', '&#8250;'],
  //   responsive: {
  //     0: {
  //       items: 1
  //     },
  //     400: {
  //       items: 2
  //     },
  //     740: {
  //       items: 3
  //     },
  //     940: {
  //       items: 4
  //     }
  //   },
  //   nav: true,
  //   autoplay: true,
  //   autoplayTimeout: 3000,
  //   autoplayHoverPause: true
  // }

  // slidesStore: string[] = [
  //   'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcREoRGyXmHy_6aIgXYqWHdOT3KjfmnuSyxypw&s',
  //   '../../../assets/Layer 2.png',
  //   '../../../assets/Paragraphcontainer.png'
  // ];
}
