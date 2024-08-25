import { Component } from '@angular/core';
import { Router } from '@angular/router';
import { AccountService } from '../../Services/account.service';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpErrorResponse } from '@angular/common/http';
import Swal from 'sweetalert2';
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
    CommonModule, 
    FormsModule,
    // CarouselModule,
    // BrowserAnimationsModule
  ],
  templateUrl: './login.component.html',
  styleUrl: './login.component.css'
})
export class LoginComponent {

  email:string = ""
  password:string = ""

  emailError: string = ""; 
  passwordError: string = ""; 

  constructor(private router: Router , public accountService:AccountService){  }

  isValidEmail(email: string): boolean {
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailPattern.test(email);
  }

  isFormValid(){
    let isValid = true
    this.emailError = "";  
    this.passwordError = ""; 

    if (this.email.trim() === "" && this.password.trim() === "") {
      isValid = false;
      this.emailError = '*Email cannot be empty';
      this.passwordError = '*Password cannot be empty';
    } else if (this.email.trim() === "") {
      isValid = false;
      this.emailError = '*Email cannot be empty';
    } else if (this.password.trim() === "") {
      isValid = false;
      this.passwordError = '*Password cannot be empty';
    } 
    if (!this.isValidEmail(this.email) && this.email.trim() !== ""){
      this.emailError = '*Invalid Email';
      isValid = false;
    }
    return isValid
  }

  onEmailChange() {
    this.emailError = "" 
  }

  onPasswordChange() {
    this.passwordError = "" 
  }
  
  SignIn(){
    if(this.isFormValid()){
      this.accountService.Login(this.email,this.password).subscribe(
        async (d: string) => {
            const response = JSON.parse(d);
            const token = response.token;
  
            if (token) {
              this.accountService.isAuthenticated = true;
              localStorage.setItem("token", token);
  
              // Wait for the data fetch to complete
              const userData = await this.accountService.GetDataFromToken().toPromise();
  
              const userResponse = JSON.parse(userData as string);
              this.accountService.r = userResponse.User;

              // Navigate based on role
              if (this.accountService.r.role_name === "Employee") {
                this.router.navigateByUrl("employee");
              } else if (this.accountService.r.role_name === "Hr") {
                this.router.navigateByUrl("HR");
              }
            }
          
        },
        (error: HttpErrorResponse) => {
          if (error.status === 401) {
            Swal.fire({
              icon: "error",
              title: "Invalid Email or Password",
              confirmButtonText: "OK",
              confirmButtonColor: "#FF7519",
            });
          } else if(error.status === 400) {
            this.passwordError = '*Password Is Less then 6 Chars';
          } else if(error.status === 500){
            Swal.fire({ 
              title: "Server is Not Available Please try in another time", 
              confirmButtonText: "OK",
              confirmButtonColor: "#FF7519",
            });
          }
        }
      );
    }
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
