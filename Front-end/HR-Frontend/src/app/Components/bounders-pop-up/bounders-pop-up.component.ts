import { Component, Inject, AfterViewInit } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { FormsModule } from '@angular/forms';
import { ReverseGeocodingService } from '../../Services/reverse-geocoding.service';
import { LocationsService } from '../../Services/locations.service';
import { CommonModule } from '@angular/common';

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
  map: any;
  marker: any;

  nameError: string = "";
  addressError: string = ""; 

  constructor(public dialogRef: MatDialogRef<BoundersPopUpComponent>, 
              public googleMapsService: ReverseGeocodingService,
              public LocationServ: LocationsService,
              @Inject(MAT_DIALOG_DATA) public data: any) {
    this.mode = data.mode;
    if (this.mode === 'edit') {
      this.Boundname = data.locationName;
      this.id = data.id;
      this.address = data.LocationAddress;
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
            console.error('Geocoding error: ', error);
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
          console.log('No details available for input: ' + place.name);
        }
      });
    } else {
      console.error('Geolocation is not supported or not running in a browser.');
    }
  }
  

  isFormValid(){
    let isValid = true
    this.nameError = ""; 
    this.addressError = "";  
    if (this.Boundname.trim() === "" && this.address.trim() === "") {
      isValid = false;
      this.nameError = '*Name Can not be empty';
      this.addressError = '*Address Can not be empty';
    } else if (this.Boundname.trim() === "") {
      isValid = false;
      this.nameError = '*Name Can not be empty';
    } else if (this.address.trim() === "") {
      isValid = false;
      this.addressError = '*Address Can not be empty';
    } 
    return isValid
  }

  onNameChange() {
    this.nameError = "" 
  }
  
  onAddressChange() {
    this.addressError = "" 
  }

  EditAndAddLocation() {
    if(this.isFormValid()){
      if (this.mode === 'edit') {
        this.LocationServ.EditByID(this.Boundname, this.address, this.lat, this.long, this.id).subscribe(
          (d: any) => {
            this.dialogRef.close();
          },
          (error) => {
            console.log(error);
          }
        );
      } else if (this.mode === 'add') {
        this.LocationServ.CreateAddress(this.Boundname, this.address, this.lat, this.long).subscribe(
          (d: any) => {
            this.dialogRef.close();
          },
          (error) => {
            console.log(error);
          }
        );
      }
    }
  }
}
