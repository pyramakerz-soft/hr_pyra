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
    image: "string",
    work_home:false
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
  UtcTime:string="";


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
        console.log(this.clockInTime)

        if(!this.userDetails.is_clocked_out){


            this.initializeStopwatchTime();
            this.startStopwatch();

        }
    });
  }

  convertUTCToEgyptLocalTime(utcTimeStr: string): string {
    const [time, period] = utcTimeStr.split(/(AM|PM)/);
    let [hours, minutes] = time.split(':').map(Number);
    if (period === 'PM' && hours !== 12) {
      hours += 12;
    }
    if (period === 'AM' && hours === 12) {
      hours = 0;
    }
    const currentDate = new Date();
    const utcDate = new Date(Date.UTC(currentDate.getUTCFullYear(), currentDate.getUTCMonth(), currentDate.getUTCDate(), hours, minutes));
    const egyptTimeZone = 'Africa/Cairo';
    const localDate = new Date(utcDate.toLocaleString('en-US', { timeZone: egyptTimeZone }));
    let localHours = localDate.getHours();
    const localMinutes = localDate.getMinutes();
    const localPeriod = localHours >= 12 ? 'PM' : 'AM';
    localHours = localHours % 12 || 12; // Converts '0' hours to '12'
    const formattedHours = String(localHours).padStart(2, '0');
    const formattedMinutes = String(localMinutes).padStart(2, '0');
    return `${formattedHours}:${formattedMinutes} ${localPeriod}`;
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
      data: { Name: this.userDetails.name , job_title: this.userDetails.job_title , work_home:this.userDetails.work_home } // Your data here
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
       await this.getLocation(); 
       this.UtcTime= this.getCurrentTimeInUTC();


      this.clockService.CreateClockOut(this.lat, this.lng ,this.UtcTime).subscribe(
        (response: any) => {
          localStorage.setItem("IsClockedIn", "false");
          this.IsClockedIn = false;
          this.clockEventService.notifyClockedIn(); // Notify other components
          this.userDetails.is_clocked_out = true;

          this.stopStopwatch();
          this.resetStopwatch();
        },
        (error: HttpErrorResponse) => {
          console.log(error)
          Swal.fire({   
            text: "Failed to retrieve location. Please try again.",
            confirmButtonText: "OK",
            confirmButtonColor: "#FF7519",
            
          });
        }
      );

     

  }

  getCurrentTimeInUTC(): string {
    const currentDate = new Date();
  
    // Extract the individual date and time components
    const year = currentDate.getUTCFullYear();
    const month = String(currentDate.getUTCMonth() + 1).padStart(2, '0'); // Months are 0-based, so add 1
    const day = String(currentDate.getUTCDate()).padStart(2, '0');
    const hours = String(currentDate.getUTCHours()).padStart(2, '0');
    const minutes = String(currentDate.getUTCMinutes()).padStart(2, '0');
    const seconds = String(currentDate.getUTCSeconds()).padStart(2, '0');
  
    // Combine components into the desired format
    const formattedUTCDate = `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
  
    return formattedUTCDate;
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


