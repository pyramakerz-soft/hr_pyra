import { Component, EventEmitter, Inject, Output } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { ClockService } from '../../Services/clock.service';
import { HttpErrorResponse } from '@angular/common/http';
import { ClockEventService } from '../../Services/clock-event.service';
import { ReverseGeocodingService } from '../../Services/reverse-geocoding.service';
import Swal from 'sweetalert2';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-clock-in-pop-up',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './clock-in-pop-up.component.html',
  styleUrl: './clock-in-pop-up.component.css'
})
export class ClockInPopUpComponent {

  public lat: number = 196.0000000;
  public lng: number = 173.0000000;
  public IsClockedIn: boolean = false;
  public reversedGeo: any;
  public locationName: string = "";
  sites: { value: string, label: string }[] = [
    { value: 'home', label: 'Home' },
    { value: 'site1', label: 'Site 1' },
    { value: 'site2', label: 'Site 2' },
    // Add more sites as needed
  ];
  EmpName: string = "";
  JobTitle: string = "";


  selectedSite: string = '';

  constructor(public dialogRef: MatDialogRef<ClockInPopUpComponent>, public clockService: ClockService, public clockEventService: ClockEventService, public revGeo: ReverseGeocodingService, @Inject(MAT_DIALOG_DATA) public data: any) {

    this.EmpName = data.Name;
    this.JobTitle = data.job_title;
    console.log(this.EmpName, this.JobTitle)

  }

  closeDialog(): void {
    this.IsClockedIn = false;
    this.dialogRef.close(this.IsClockedIn);
  }

  public async ngOnInit(): Promise<void> {
    await this.getLocation();

    const result = await this.revGeo.getAddress(this.lat, this.lng);
    this.reversedGeo = result.formatted_address;
    this.locationName = result.address_components[1].long_name + " , " + result.address_components[2].long_name + " , " + result.address_components[3].long_name
  }




  async sendLocation(): Promise<void> {
    await this.getLocation();
    this.clockService.CreateClockIn(this.lat, this.lng).subscribe(
      (response: any) => {
        this.IsClockedIn = true;
        this.dialogRef.close(this.IsClockedIn);
        this.clockEventService.notifyClockedIn();
      },
      (error: HttpErrorResponse) => {
        const errorMessage = error.error?.message || 'An unknown error occurred';
        Swal.fire({
          text: "Failed to retrieve location. Please try again.",
          confirmButtonText: "OK",
          confirmButtonColor: "#FF7519",

        });
        console.log(this.lat, this.lng)
      }
    );
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




  getCurrentFormattedTime(): string {
    const now = new Date();
    let hours = now.getHours();
    const minutes = now.getMinutes();
    const ampm = hours >= 12 ? 'PM' : 'AM';

    hours = hours % 12;
    hours = hours ? hours : 12; // the hour '0' should be '12'
    const minutesStr = minutes < 10 ? '0' + minutes : minutes.toString();

    return `${hours}:${minutesStr} ${ampm}`;
  }

  getCurrentDate(): string {
    const date = new Date();
    return date.toLocaleDateString('en-US', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    });
  }

}