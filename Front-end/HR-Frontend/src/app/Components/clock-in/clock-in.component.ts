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
import { TimeApiService } from '../../Services/time-api.service';

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
    name: "",
    job_title: "",
    id: "",
    role_name: "",
    is_clocked_out: true,
    national_id: "",
    clockIn: "",
    image: "",
    work_home: false,
    total_hours: ""
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
  UtcTime: string = "";


  isDateLoaded = false

  constructor(public dialog: MatDialog, public accountService: AccountService, public clockService: ClockService, public clockEventService: ClockEventService , public TimeApi:TimeApiService) {
  }


  ngOnInit(): void {
    this.getDataFromToken();


    this.currentDate = this.getCurrentDate();

    this.clockEventService.clockedIn$.subscribe(() => {
      this.userDetails.is_clocked_out = !this.userDetails.is_clocked_out;
    });
  }

  getDataFromToken(): void {
    this.accountService.GetDataFromToken().subscribe((d: string) => {
      const response = JSON.parse(d);
      this.userDetails = response.User;
      this.isDateLoaded = true
      this.stopwatchTime = this.convertTimeToSeconds(this.userDetails.total_hours) || 0;

      if (!this.userDetails.is_clocked_out) {
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
      data: { Name: this.userDetails.name, job_title: this.userDetails.job_title, work_home: this.userDetails.work_home, isClockInFromHrToOtherUser:false, userId: null }
    });
    dialogRef.afterClosed().subscribe(result => {
      if (result !== undefined) {
        this.IsClockedIn = result;
        if (result == true) {
          this.startStopwatch();
          this.userDetails.is_clocked_out = false;

        }
      }
    });

  }


  async sendLocation(): Promise<void> {
    await this.getLocation();
    this.UtcTime = await this.getCurrentTimeInUTC();

    Swal.fire({
      title: 'Are you sure you want to Clock out?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#FF7519',
      cancelButtonColor: '#17253E',
      confirmButtonText: 'Clock Out',
      cancelButtonText: 'Cancel'
    }).then((result) => {
      if (result.isConfirmed) {


        
        this.clockService.CreateClockOut(this.lat, this.lng, this.UtcTime).subscribe(

          (response: any) => {
            localStorage.setItem("IsClockedIn", "false");
            this.IsClockedIn = false;
            this.clockEventService.notifyClockedIn(); // Notify other components
            this.userDetails.is_clocked_out = true;

            this.stopStopwatch();
            // this.resetStopwatch();
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
    });

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
          },
          {
            enableHighAccuracy: true,
            maximumAge: 0, // Disable caching for the most up-to-date reading
            timeout: 10000 // Allow up to 10 seconds to retrieve location
          }
        );
      } else {
        console.warn('Geolocation is not supported or not running in a browser.');
        reject(new Error('Geolocation is not supported or not running in a browser.'));
      }
    });
  }



  convertTimeToSeconds(time: string): number | null {
    if (!time) return null; // Return null if time is undefined or empty

    const timeParts = time.split(':');
    if (timeParts.length !== 3) return null; // Ensure there are exactly 3 parts (hh:mm:ss)

    const [hours, minutes, seconds] = timeParts.map(Number);

    if (isNaN(hours) || isNaN(minutes) || isNaN(seconds)) {
      return null; // Return null if any part is not a number
    }

    return hours * 3600 + minutes * 60 + seconds;
  }

  startStopwatch(): void {
    if (!this.isRunning) {
      this.isRunning = true;
      this.interval = setInterval(() => {
        this.stopwatchTime++; // Increment as seconds
      }, 1000);
    }
  }


  stopStopwatch(): void {
    if (this.isRunning) {
      this.isRunning = false;
      clearInterval(this.interval);
    }
  }


  getFormattedTime(): string {
    const hours = Math.floor(this.stopwatchTime / 3600);
    const minutes = Math.floor((this.stopwatchTime % 3600) / 60);
    const seconds = this.stopwatchTime % 60;
    return `${this.pad(hours)}:${this.pad(minutes)}:${this.pad(seconds)}`;
  }

  pad(value: number): string {
    return value < 10 ? `0${value}` : String(value);
  }


  getCurrentTimeInUTC(): Promise<string> {
    return new Promise((resolve, reject) => {
      const timestamp = Math.floor(Date.now() / 1000); 

      this.TimeApi.getCurrentTimeGoogle(this.lat, this.lng).subscribe((response) => {
        if (response ) {
          const dstOffset = response.dstOffset;
          const rawOffset = response.rawOffset;

          const totalOffsetInSeconds = dstOffset + rawOffset;
          
          const localTimestamp = timestamp + totalOffsetInSeconds;
          
          const utcTimestamp = localTimestamp - totalOffsetInSeconds;

          const utcDate = new Date(utcTimestamp * 1000);

          const year = utcDate.getUTCFullYear();
          const month = ('0' + (utcDate.getUTCMonth() + 1)).slice(-2); 
          const day = ('0' + utcDate.getUTCDate()).slice(-2);
          const hours = ('0' + utcDate.getUTCHours()).slice(-2);
          const minutes = ('0' + utcDate.getUTCMinutes()).slice(-2);
          const seconds = ('0' + utcDate.getUTCSeconds()).slice(-2);

          const formattedDateTime = `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
          resolve(formattedDateTime); // Resolve the promise with formatted UTC time
        } else {
          console.error('Error fetching time zone data:');
          reject('Error fetching time zone data');
        }
      }, (error) => {
        console.error('Subscription error:', error);
        reject('Subscription error');
      });
    });
  }

  

}






  // getCurrentTimeInUTC(): string {
  //   const currentDate = new Date();

  //   // Extract the individual date and time components
  //   const year = currentDate.getUTCFullYear();
  //   const month = String(currentDate.getUTCMonth() + 1).padStart(2, '0'); // Months are 0-based, so add 1
  //   const day = String(currentDate.getUTCDate()).padStart(2, '0');
  //   const hours = String(currentDate.getUTCHours()).padStart(2, '0');
  //   const minutes = String(currentDate.getUTCMinutes()).padStart(2, '0');
  //   const seconds = String(currentDate.getUTCSeconds()).padStart(2, '0');

  //   // Combine components into the desired format
  //   const formattedUTCDate = `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;

  //   return formattedUTCDate;
  // }


    // resetStopwatch(): void {
  //   this.stopwatchTime = 0;
  // }

    // getCurrentTimeInSeconds(): number {
  //   const now = new Date();
  //   return now.getHours() * 3600 + now.getMinutes() * 60 + now.getSeconds();
  // }

  
  // initializeStopwatchTime(): void {
  //   const clockInSeconds = this.convertTimeToSeconds(this.clockInTime);
  //   const currentSeconds = this.getCurrentTimeInSeconds();
  //   this.stopwatchTime = currentSeconds - clockInSeconds;
  // }
