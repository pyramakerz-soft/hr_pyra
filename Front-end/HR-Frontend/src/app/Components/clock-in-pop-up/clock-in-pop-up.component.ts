import { Component, EventEmitter, Output } from '@angular/core';
import { MatDialogRef } from '@angular/material/dialog';
import { ClockService } from '../../Services/clock.service';
import { HttpErrorResponse } from '@angular/common/http';
import { ClockEventService } from '../../Services/clock-event.service';

@Component({
  selector: 'app-clock-in-pop-up',
  standalone: true,
  imports: [],
  templateUrl: './clock-in-pop-up.component.html',
  styleUrl: './clock-in-pop-up.component.css'
})
export class ClockInPopUpComponent {

  public lat: number =196.0000000;
  public lng: number =173.0000000;
  public IsClockedIn:boolean=false;

  constructor(public dialogRef: MatDialogRef<ClockInPopUpComponent>,public clockService:ClockService , public clockEventService: ClockEventService) { }

  closeDialog(): void {
    this.IsClockedIn=false;
    this.dialogRef.close(this.IsClockedIn);
  }

  public ngOnInit(): void {

  }

  async sendLocation(): Promise<void> {
    try {
    // await this.getLocation();  
      this.clockService.CreateClockIn(this.lat, this.lng).subscribe(
        (response: any) => {
          this.IsClockedIn = true;
          this.dialogRef.close(this.IsClockedIn);
          this.clockEventService.notifyClockedIn(); // Notify other components
        },
        (error: HttpErrorResponse) => {
          const errorMessage = error.error?.message || 'An unknown error occurred';
          alert(errorMessage); 
        }
      );
    } catch (error) {
      console.error('Error getting location:', error);
      alert('Failed to retrieve location. Please try again.');
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
            console.error(error);
            alert("Error retrieving location: " + error.message);
            reject(error);  
          }
        );
      } else {
        console.warn('Geolocation is not supported or not running in a browser.');
        reject(new Error('Geolocation is not supported or not running in a browser.'));
      }
    });
  }
}