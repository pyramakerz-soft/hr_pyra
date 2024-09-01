import { Component, EventEmitter, Inject, Output } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { ClockService } from '../../Services/clock.service';
import { HttpErrorResponse } from '@angular/common/http';
import { ClockEventService } from '../../Services/clock-event.service';
import { ReverseGeocodingService } from '../../Services/reverse-geocoding.service';
import Swal from 'sweetalert2';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { WorkTypeService } from '../../Services/work-type.service';
import { WorkType } from '../../Models/work-type';
import { LocationsService } from '../../Services/locations.service';
import { AssignLocationToUser } from '../../Models/assign-location-to-user';
import { TimeApiService } from '../../Services/time-api.service';
import { Observable, of } from 'rxjs';

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
  WorkHome:boolean=false;
  isClockInFromHrToOtherUser: boolean = false;
  isDropdownOpen = false;
  sites:WorkType[] = [];
  Locations: AssignLocationToUser[] = [];
  public locationName: string = "";
  EmpName: string = "";
  JobTitle: string = "";
  UtcTime:string="";
  selectedSite: string = '';
  public reversedGeo: any;

  userId:number|null = null
  TimeClockInFromHrForOthers:string = ""
  DateClockInFromHrForOthers:string = ""
  LocationClockInFromHrForOthers:number = 0

  constructor(public dialogRef: MatDialogRef<ClockInPopUpComponent>, public clockService: ClockService, 
    public clockEventService: ClockEventService, public revGeo: ReverseGeocodingService, @Inject(MAT_DIALOG_DATA) public data: any , 
    public locationService: LocationsService, public workType:WorkTypeService , public TimeApi:TimeApiService) {
      this.EmpName = data.Name;
      this.JobTitle = data.job_title;
      this.WorkHome=data.work_home;
      this.isClockInFromHrToOtherUser=data.isClockInFromHrToOtherUser;
      this.userId=data.userId;
  }

  closeDialog(): void {
    this.IsClockedIn = false;
    this.dialogRef.close(this.IsClockedIn);
  }

  public async ngOnInit(): Promise<void> {
    if(this.isClockInFromHrToOtherUser == false){
      await this.getLocation();
      const result = await this.revGeo.getAddress(this.lat, this.lng);
      this.reversedGeo = result.formatted_address;
      this.locationName = result.address_components[1].long_name + " , " + result.address_components[2].long_name + " , " + result.address_components[3].long_name
    }else{
      this.getLocationsFromServerByUserId()
    }
    this.getWorkTypes()
  }

  getCurrentTimeInUTC(): any {

    this.TimeApi.getCurrentTime().subscribe(
      (response) => {
        const currentDate = new Date(response.datetime);

        // Extract the individual date and time components
        const year = currentDate.getUTCFullYear();
        const month = String(currentDate.getUTCMonth() + 1).padStart(2, '0');
        const day = String(currentDate.getUTCDate()).padStart(2, '0');
        const hours = String(currentDate.getUTCHours()).padStart(2, '0');
        const minutes = String(currentDate.getUTCMinutes()).padStart(2, '0');
        const seconds = String(currentDate.getUTCSeconds()).padStart(2, '0');

        // Combine components into the desired format
        const currentUTCDateTime = `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
        return currentUTCDateTime;
      },
      (error) => {
        return of('error');

      }
    );
  }


  
  async sendLocationByToken(): Promise<void> {
    await this.getLocation();
    this.UtcTime= this.getCurrentTimeInUTC();

    if(this.WorkHome==false){
    this.clockService.CreateClockIn(this.lat, this.lng ,this.UtcTime ,"site").subscribe(
      (response: any) => {
        this.IsClockedIn = true;
        this.dialogRef.close(this.IsClockedIn);
        this.clockEventService.notifyClockedIn();
      },
      (error: HttpErrorResponse) => {
        const errorMessage = error.error?.message || 'An unknown error occurred';
        console.log(error.error.message)
        if(error.error.message.includes("The location type field is required")){
          Swal.fire({
            text: "The location type field is required",
            confirmButtonText: "OK",
            confirmButtonColor: "#FF7519",

          });
        }
      else  if (error.error.message.includes("User is not located at the correct location")){
        Swal.fire({
          text: "You Are not located at the correct location",
          confirmButtonText: "OK",
          confirmButtonColor: "#FF7519",

        });
      }
      else{
        Swal.fire({
          text: "Try In Another Time",
          confirmButtonText: "OK",
          confirmButtonColor: "#FF7519",

        });
      }
        console.log(this.lat, this.lng)
      }
    );
    }
    else{
      this.clockService.CreateClockIn(this.lat, this.lng , this.UtcTime,this.selectedSite).subscribe(
        (response: any) => {
          this.IsClockedIn = true;
          this.dialogRef.close(this.IsClockedIn);
          this.clockEventService.notifyClockedIn();
        },
        (error: HttpErrorResponse) => {
          const errorMessage = error.error?.message || 'An unknown error occurred';
          if(error.error.message.includes("The location type field is required")){
            Swal.fire({
              text: "The location type field is required",
              confirmButtonText: "OK",
              confirmButtonColor: "#FF7519",

            });
          }
        else  if (error.error.message.includes("User is not located at the correct location")){
          Swal.fire({
            text: "You Are not located at the correct location",
            confirmButtonText: "OK",
            confirmButtonColor: "#FF7519",

          });
        }
        else{
          console.log(error.error.message)
          Swal.fire({
            text: "Try In Another Time",
            confirmButtonText: "OK",
            confirmButtonColor: "#FF7519",

          });
        }
          console.log(this.lat, this.lng)
        }
      );
    }
  }

  sendLocationByHrForOthers(){
    const clockIn = this.DateClockInFromHrForOthers + " " + this.TimeClockInFromHrForOthers + ":00"
    if(this.userId){
      console.log(this.LocationClockInFromHrForOthers)
      console.log(this.userId)
      console.log(clockIn)
      if(this.WorkHome==false){
        this.clockService.CreateClockInByHrForOther(this.userId, this.LocationClockInFromHrForOthers, clockIn ,"site").subscribe(
          (response: any) => {
            this.IsClockedIn = true;
            this.dialogRef.close(this.IsClockedIn);
            this.clockEventService.notifyClockedIn();
          },
          (error: HttpErrorResponse) => {
            console.log(error.error.message)
            Swal.fire({
              text: "Try In Another Time",
              confirmButtonText: "OK",
              confirmButtonColor: "#FF7519",
  
            });
          }
        );
      }
      else{
        this.clockService.CreateClockInByHrForOther(this.userId, this.LocationClockInFromHrForOthers, clockIn,this.selectedSite).subscribe(
          (response: any) => {
            this.IsClockedIn = true;
            this.dialogRef.close(this.IsClockedIn);
            this.clockEventService.notifyClockedIn();
          },
          (error: HttpErrorResponse) => {
            console.log(error.error.message)
            Swal.fire({
              text: "Try In Another Time",
              confirmButtonText: "OK",
              confirmButtonColor: "#FF7519",
  
            });
          }
        );
      }
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
 
  getCurrentFormattedTime(): any {


    this.TimeApi.getCurrentTime().subscribe(
      (response) => {
        const currentDate = new Date(response.datetime);

        // Extract the individual date and time components
        const year = currentDate.getUTCFullYear();
        const month = String(currentDate.getUTCMonth() + 1).padStart(2, '0');
        const day = String(currentDate.getUTCDate()).padStart(2, '0');
        const hours = String(currentDate.getUTCHours()).padStart(2, '0');
        const minutes = String(currentDate.getUTCMinutes()).padStart(2, '0');
        const seconds = String(currentDate.getUTCSeconds()).padStart(2, '0');

        // Combine components into the desired format
        const currentUTCDateTime = `${hours}:${minutes}:${seconds}`;
        console.log(currentDate)
        return currentUTCDateTime;
      },
      (error) => {
        return of('error');

      }
    );

    // const now = new Date();
    // let hours = now.getHours();
    // const minutes = now.getMinutes();
    // const ampm = hours >= 12 ? 'PM' : 'AM';

    // hours = hours % 12;
    // hours = hours ? hours : 12; // the hour '0' should be '12'
    // const minutesStr = minutes < 10 ? '0' + minutes : minutes.toString();

    // return `${hours}:${minutesStr} ${ampm}`;
  }

  getCurrentDate(): string {
    const date = new Date();
    return date.toLocaleDateString('en-US', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    });
  }

  getWorkTypes(){
    this.workType.getall().subscribe(
      (workTypes: any) => {
        this.sites = workTypes.workTypes
      }
    );
  }

  getLocationsFromServerByUserId(){
    if(this.userId){
      this.locationService.GetLocationsByUserId(this.userId).subscribe(
        (locations: any) => {
          console.log(locations)
          this.Locations = locations.userLocations
        } 
      );
    }
  }

  toggleDropdown() {
    this.isDropdownOpen = !this.isDropdownOpen;
  }
}