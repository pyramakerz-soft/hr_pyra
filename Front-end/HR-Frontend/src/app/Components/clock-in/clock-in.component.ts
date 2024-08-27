import { Component, ViewChild } from '@angular/core';
import { MatDialog, MatDialogModule } from '@angular/material/dialog'
import { ClockInPopUpComponent } from '../clock-in-pop-up/clock-in-pop-up.component';
import { AccountService } from '../../Services/account.service';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';
import { ClockService } from '../../Services/clock.service';
import { HttpErrorResponse } from '@angular/common/http';
import { TableComponent } from '../Core/table/table.component';
import { ClockEventService } from '../../Services/clock-event.service';
import { UserDetails } from '../../Models/user-details';
import Swal from 'sweetalert2';

@Component({
  selector: 'app-clock-in',
  standalone: true,
  imports: [
    MatDialogModule,
    FormsModule,
    CommonModule

  ],
  templateUrl: './clock-in.component.html',
  styleUrl: './clock-in.component.css'
})
export class ClockInComponent {

  userDetails: UserDetails = {
    name: "string",
    job_title: "string",
    id: "string",
    role_name: "string",
    is_clocked_out: true,
    national_id: "string",
    clockIn: "string",
    image: "string"
  };  
  currentDate: string | undefined;
  token: string = "";
  public IsClockedIn: boolean = false;
  public lat: number = 196.0000000;
  public lng: number = 173.0000000;
  @ViewChild(TableComponent) tableComponent!: TableComponent;
  stopwatchTime: number = 0; // Time in seconds
  interval: any;
  isRunning: boolean = false;
  clockInTime: string = "15:23:32"; 

  constructor(public dialog: MatDialog, public accountService: AccountService, public clockService: ClockService, public clockEventService: ClockEventService) {
  }


  ngOnInit(): void {
    this.getDataFromToken();


    this.currentDate = this.getCurrentDate();

    this.clockEventService.clockedIn$.subscribe(() => {
      this.userDetails.is_clocked_out = !this.userDetails.is_clocked_out;
    });
  }

  getDataFromToken():void{
    this.accountService.GetDataFromToken().subscribe((d: string) => {
        const response = JSON.parse(d);
        this.userDetails = response.User;
        this.clockInTime=this.userDetails.clockIn;
        if(!this.userDetails.is_clocked_out){


            this.initializeStopwatchTime();
            this.startStopwatch();

        }
    });
  }

  getCurrentDate(): string {
    const date = new Date();
    return date.toLocaleDateString('en-US', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    });
  }

  openDialog(): void {
    const dialogRef = this.dialog.open(ClockInPopUpComponent, {
      data: { Name: this.userDetails.name , job_title: this.userDetails.job_title ,  } // Your data here
    });
    dialogRef.afterClosed().subscribe(result => {
      if (result !== undefined) {
        this.IsClockedIn = result;
        if(result==true){
          this.startStopwatch();
          this.userDetails.is_clocked_out = false;

        }
      }
    });

  }


  async sendLocation(): Promise<void> {
    try {
       await this.getLocation();  
      this.clockService.CreateClockOut(this.lat, this.lng).subscribe(
        (response: any) => {
          localStorage.setItem("IsClockedIn", "false");
          this.IsClockedIn = false;
          this.clockEventService.notifyClockedIn(); // Notify other components
          this.userDetails.is_clocked_out = true;

          this.stopStopwatch();
          this.resetStopwatch();
        },
        (error: HttpErrorResponse) => {
          const errorMessage = error.error?.message || 'An unknown error occurred';
          console.log(errorMessage);
        }
      );
    } catch (error) {
      Swal.fire({   
        text: "Failed to retrieve location. Please try again.",
        confirmButtonText: "OK",
        confirmButtonColor: "#FF7519",
        
      });
    }
  }


  getLocation(): Promise<void> {
    return new Promise((resolve, reject) => {
      if (typeof window !== 'undefined' && navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
          (position: GeolocationPosition) => {
            this.lat = position.coords.latitude;
            this.lng = position.coords.longitude;
            resolve();
          },
          (error: GeolocationPositionError) => {
            Swal.fire({   
              text: "Error retrieving location.",
              confirmButtonText: "OK",
              confirmButtonColor: "#FF7519",
              
            });
            reject(error);
          }
        );
      } else {
        console.warn('Geolocation is not supported or not running in a browser.');
        reject(new Error('Geolocation is not supported or not running in a browser.'));
      }
    });
  }


  initializeStopwatchTime(): void {
    const clockInSeconds = this.convertTimeToSeconds(this.clockInTime);
    const currentSeconds = this.getCurrentTimeInSeconds();
    this.stopwatchTime = currentSeconds - clockInSeconds;
  }

  convertTimeToSeconds(time: string): number {
    const [hours, minutes, seconds] = time.split(':').map(Number);
    return hours * 3600 + minutes * 60 + seconds;
  }

  getCurrentTimeInSeconds(): number {
    const now = new Date();
    return now.getHours() * 3600 + now.getMinutes() * 60 + now.getSeconds();
  }

  startStopwatch(): void {
    if (!this.isRunning) {
      this.isRunning = true;
      this.interval = setInterval(() => {
        this.stopwatchTime++;
      }, 1000);
    }
  }

  stopStopwatch(): void {
    if (this.isRunning) {
      this.isRunning = false;
      clearInterval(this.interval);
    }
  }

  resetStopwatch(): void {
    this.stopwatchTime = 0;
  }

  getFormattedTime(): string {
    const hours = Math.floor(this.stopwatchTime / 3600);
    const minutes = Math.floor((this.stopwatchTime % 3600) / 60);
    const seconds = this.stopwatchTime % 60;
    return `${this.pad(hours)} : ${this.pad(minutes)} : ${this.pad(seconds)}`;
  }

  pad(time: number): string {
    return time < 10 ? `0${time}` : `${time}`;
  }
}


