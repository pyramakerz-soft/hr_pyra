import { Component, Inject, AfterViewInit } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { FormsModule } from '@angular/forms';
import { ReverseGeocodingService } from '../../Services/reverse-geocoding.service';
import { LocationsService } from '../../Services/locations.service';
import { CommonModule } from '@angular/common';
import Swal from 'sweetalert2';

declare const google: any;

@Component({
  selector: 'app-bounders-pop-up',
  standalone: true,
  imports: [FormsModule, CommonModule],
  templateUrl: './bounders-pop-up.component.html',
  styleUrls: ['./bounders-pop-up.component.css']
})
export class BoundersPopUpComponent implements AfterViewInit {
  location: string = '';
  mode: string = 'add';
  id: number = 1;
  lat: number = 0;
  long: number = 0;
  Boundname: string = '';
  address: string = '';
  radius: string = '';
  map: any;
  marker: any;

  nameError: string = "";
  addressError: string = ""; 
  radiusError: string = ""; 

  StartTime:string=""
  EndTime:string=""
  StartTimeError:string=""
  EndTimeError:string=""

  STime:string=""
  ETime:string=""

  SaveButton:boolean=false;

  constructor(public dialogRef: MatDialogRef<BoundersPopUpComponent>, 
              public googleMapsService: ReverseGeocodingService,
              public LocationServ: LocationsService,
              @Inject(MAT_DIALOG_DATA) public data: any) {
    this.mode = data.mode;
    if (this.mode === 'edit') {
      this.Boundname = data.locationName;
      this.id = data.id;
      this.address = data.LocationAddress;
      this.lat=data.Lat,
      this.long=data.Long,
      this.radius = data.radius
      this.StartTime=this.convertUtcToEgyptianTime(data.startTime)
      this.EndTime=this.convertUtcToEgyptianTime(data.endTime)
      this.radius=data.range
    }
  }

  closeDialog(): void {
    this.dialogRef.close();
  }

  async ngAfterViewInit() {
    await this.googleMapsService.load();
  
    if (typeof google !== 'undefined') {
      const mapOptions = {
        center: new google.maps.LatLng(this.lat || 30.0444, this.long || 31.2357), // Default to Cairo, Egypt
        zoom: 15
      };
  
      this.map = new google.maps.Map(document.getElementById('map'), mapOptions);
  
      this.marker = new google.maps.Marker({
        position: mapOptions.center,
        map: this.map,
        draggable: true
      });
  
      google.maps.event.addListener(this.marker, 'dragend', () => {
        const position = this.marker.getPosition();
        this.lat = position.lat();
        this.long = position.lng();
  
        // Reverse geocoding to get address
        this.googleMapsService.getAddress(this.lat, this.long).then(
          (result: any) => {
            this.address = result.formatted_address;
            
            // Update the autocomplete input field with the new address
            const autocompleteInput = document.getElementById('autocomplete') as HTMLInputElement;
            if (autocompleteInput) {
              autocompleteInput.value = this.address || '';
            }
          },
          (error) => {
            Swal.fire({
              text: "Geocoding error",
              confirmButtonText: "OK",
              confirmButtonColor: "#FF7519",
            })
          }
        );
      });
  
      const autocomplete = new google.maps.places.Autocomplete(
        document.getElementById('autocomplete') as HTMLInputElement,
        {
          types: ['geocode'],
          componentRestrictions: { country: 'EG' }
        }
      );
  
      autocomplete.addListener('place_changed', () => {
        const place = autocomplete.getPlace();
        if (place.geometry && place.geometry.location) {
          const location = place.geometry.location;
          this.map.setCenter(location);
          this.marker.setPosition(location);
          this.lat = location.lat();
          this.long = location.lng();
          this.address = place.formatted_address;
  
          // Update the autocomplete input field with the new address
          const autocompleteInput = document.getElementById('autocomplete') as HTMLInputElement;
          if (autocompleteInput) {
            autocompleteInput.value = this.address || '';
          }
        } else {
        }
      });
    } else {
      Swal.fire({
        text: "Geolocation is not supported or not running in a browser",
        confirmButtonText: "OK",
        confirmButtonColor: "#FF7519",
      }
      )
    }
  }
  

  isFormValid(){
    let isValid = true
    this.nameError = ""; 
    this.addressError = "";  
    this.radiusError = "";  
    this.StartTimeError="";
    this.EndTimeError="";

    if (this.Boundname.trim() === "" && this.address.trim() === "" && this.StartTime.trim() === "" && this.EndTime.trim() === "" && this.radius.trim() === "" ) {
      isValid = false;
      this.nameError = '*Name Can not be empty';
      this.addressError = '*Address Can not be empty';
      this.radiusError = '*Radius Can not be empty';
      this.StartTimeError = '*StartTime Can not be empty';
      this.EndTimeError = '*EndTime Can not be empty';

    } else if (this.Boundname.trim() === "") {
      isValid = false;
      this.nameError = '*Name Can not be empty';
    } else if (this.address.trim() === "") {
      isValid = false;
      this.addressError = '*Address Can not be empty';
    } else if (this.StartTime.trim() === "") {
      isValid = false;
      this.StartTimeError = '*StartTime Can not be empty';
    } else if (this.EndTime.trim() === "") {
      isValid = false;
      this.EndTimeError = '*EndTime Can not be empty';
    } else if (this.radius.trim() === "") {
      isValid = false;
      this.radiusError = '*Radius Can not be empty';
    } 
    return isValid
  }

  onNameChange() {
    this.nameError = "" 
  }
  
  onAddressChange() {
    this.addressError = "" 
  }
  onStartTimeChange() {
    this.StartTimeError = "" 
  }
  onEndTimeChange() {
    this.EndTimeError = "" 
  }
  onRadiusChange() {
    this.radiusError = "" 
  }

  filterNumericInput(event: Event) {
    const input = event.target as HTMLInputElement;
    let previousValue = input.value;

    input.addEventListener('input', function() {
        let newValue = input.value.replace(/[^0-9.]/g, '');

        if (newValue.split('.').length > 2) {
            input.value = previousValue; 
        } else {
            input.value = newValue; 
            previousValue = input.value; 
        }
    });
  }

  async EditAndAddLocation() {
    if(this.isFormValid()){
      this.SaveButton=true;
      this.STime=await this.convertEgyptianToUtcTime(this.StartTime)
      this.ETime=await this.convertEgyptianToUtcTime(this.EndTime)

      if(this.ETime>this.STime){
      if (this.mode === 'edit') {

        this.LocationServ.EditByID(this.Boundname, this.address, this.lat, this.long, this.id ,this.STime,this.ETime, this.radius).subscribe(
          (d: any) => {
            this.dialogRef.close();
          },
          (error) => {
            this.SaveButton=false;
            if (error.error.message === "The name has already been taken.") {
              Swal.fire({   
                text: "The Location name has already been taken",
                confirmButtonText: "OK",
                confirmButtonColor: "#FF7519",
              });
            }else{
              Swal.fire({   
                text: "Faild to create, Please Try again later",
                confirmButtonText: "OK",
                confirmButtonColor: "#FF7519",
                
              });
            }
          }
        );
      } else if (this.mode === 'add') {
        this.LocationServ.CreateAddress(this.Boundname, this.address, this.lat, this.long ,this.STime,this.ETime, this.radius).subscribe(
          (d: any) => {
            this.dialogRef.close();
          },
          (error) => {
            this.SaveButton=false;
            if (error.error.message === "The name has already been taken.") {
              Swal.fire({   
                text: "The Location name has already been taken",
                confirmButtonText: "OK",
                confirmButtonColor: "#FF7519",
              });
            }else{
              Swal.fire({   
                text: "Faild to create, Please Try again later",
                confirmButtonText: "OK",
                confirmButtonColor: "#FF7519",
                
              });
            }
          }
        );
      }
    }
    else{
      this.SaveButton=false;
      Swal.fire({   
        text: "End Time Should Be After Start Time",
        confirmButtonText: "OK",
        confirmButtonColor: "#FF7519",
      });
    }
    }
  }


  
  // convertUtcToEgyptianTime(utcTime: string): string {
  //   // Parse the input UTC time
  //   console.log("ugiy",utcTime)
  //   const [hours, minutes, seconds] = utcTime.split(':').map(Number);

  //   // Create a Date object for UTC time
  //   const utcDate = new Date(Date.UTC(1970, 0, 1, hours, minutes, seconds));

  //   // Convert to Egyptian time zone (UTC+3)
  //   const egyptianOffset = 3; // Egypt is UTC+3
  //   utcDate.setHours(utcDate.getHours() + egyptianOffset);

  //   // Format the time in HH:mm:ss format
  //   const formattedHours = String(utcDate.getUTCHours()).padStart(2, '0');
  //   const formattedMinutes = String(utcDate.getUTCMinutes()).padStart(2, '0');
  //   const formattedSeconds = String(utcDate.getUTCSeconds()).padStart(2, '0');
  //   console.log(`${formattedHours}:${formattedMinutes}:${formattedSeconds}`)

  //   return `${formattedHours}:${formattedMinutes}:${formattedSeconds}`;
  // }



  convertUtcToEgyptianTime(utcTime: string) {
    // Parse the input UTC time (HH:mm:ss)

    const [hours, minutes, seconds] = utcTime.split(':').map(Number);
  
    // Get the current date to set a complete UTC date with the provided time
    const currentDate = new Date();
    const utcDate = new Date(Date.UTC(currentDate.getUTCFullYear(), currentDate.getUTCMonth(), currentDate.getUTCDate(), hours, minutes, seconds));
  
    // Convert to Egypt local time
    const egyptTimeZone = 'Africa/Cairo';
    const localDate = new Date(utcDate.toLocaleString('en-US', { timeZone: egyptTimeZone }));
  
    // Get the local time in Egypt and format it in HH:mm:ss
    const localHours = String(localDate.getHours()).padStart(2, '0');
    const localMinutes = String(localDate.getMinutes()).padStart(2, '0');
    const localSeconds = String(localDate.getSeconds()).padStart(2, '0');

    // Return the formatted time in HH:mm:ss
    return `${localHours}:${localMinutes}:${localSeconds}`;
  }
  

  // convertEgyptianToUtcTime(egyptianTime: string): string {
  //   // Parse the input Egyptian time
  //   console.log(egyptianTime)
  //   const [hours, minutes] = egyptianTime.split(':').map(Number);

  //   // Create a Date object for Egyptian time
  //   const egyptianDate = new Date(Date.UTC(1970, 0, 1, hours, minutes));

  //   // Convert to UTC time by subtracting the Egyptian offset (UTC+3)
  //   const utcOffset = -3; // UTC-3 to convert back to UTC
  //   egyptianDate.setHours(egyptianDate.getHours() + utcOffset);

  //   // Format the time in HH:mm:ss format
  //   const formattedHours = String(egyptianDate.getUTCHours()).padStart(2, '0');
  //   const formattedMinutes = String(egyptianDate.getUTCMinutes()).padStart(2, '0');
  //   const formattedSeconds = String(egyptianDate.getUTCSeconds()).padStart(2, '0');
    
  //   console.log(`${formattedHours}:${formattedMinutes}`)
  //   return `${formattedHours}:${formattedMinutes}`;
  // }

   convertEgyptianToUtcTime(egyptianTime: string): string {
    // Parse the input Egyptian time (HH:mm)
    const [hours, minutes] = egyptianTime.split(':').map(Number);
  
    // Get the current date, but use the input time (hours, minutes) as the time part
    const currentDate = new Date();
    const egyptianDate = new Date(currentDate.getFullYear(), currentDate.getMonth(), currentDate.getDate(), hours, minutes);
  
    // Format the Egyptian time in UTC by using the 'Africa/Cairo' timezone
    const utcDate = new Date(egyptianDate.toLocaleString('en-US', { timeZone: 'Africa/Cairo' }));
  
    // Get the UTC time
    const utcHours = String(utcDate.getUTCHours()).padStart(2, '0');
    const utcMinutes = String(utcDate.getUTCMinutes()).padStart(2, '0');
  
    // Return the formatted time in HH:mm
    return `${utcHours}:${utcMinutes}`;
  }
  


  
}
