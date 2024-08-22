import { Component, Inject } from '@angular/core';
import { ImportEmployeeDataPopUpComponent } from '../import-employee-data-pop-up/import-employee-data-pop-up.component';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { FormsModule } from '@angular/forms';
import { ReverseGeocodingService } from '../../Services/reverse-geocoding.service';
import { LocationsService } from '../../Services/locations.service';

@Component({
  selector: 'app-bounders-pop-up',
  standalone: true,
  imports: [FormsModule],
  templateUrl: './bounders-pop-up.component.html',
  styleUrl: './bounders-pop-up.component.css'
})
export class BoundersPopUpComponent {
  location: string = '';
  mode: string = 'add';
  id:number=1;
  lat:number=0;
  long:number=0;
  name:string | undefined;
  address:string| undefined;


  constructor(public dialogRef: MatDialogRef<ImportEmployeeDataPopUpComponent>, public googleMapsService:ReverseGeocodingService,public LocationServ:LocationsService ,
              @Inject(MAT_DIALOG_DATA) public data: any){
                this.mode = data.mode;
                if (this.mode === 'edit') {
                  this.name = data.locationName;
                  this.id=data.id;
                  this.address=data.LocationAddress

                }
              }
  
  closeDialog(): void {
    this.dialogRef.close();
  }

  async ngAfterViewInit() {
    
  if (typeof window !== 'undefined' && typeof document !== 'undefined') {
    await this.googleMapsService.load();
    if (typeof google !== 'undefined' ) {
      const autocomplete = new google.maps.places.Autocomplete(
        document.getElementById('autocomplete') as HTMLInputElement,
        {
          types: ['geocode'],
          componentRestrictions: { country: 'EG' } // Restrict to Egypt
        }
      );

      autocomplete.addListener('place_changed', () => {
        const place = autocomplete.getPlace();
        if (place.geometry && place.geometry.location) {
          const latitude = place.geometry.location.lat();
          const longitude = place.geometry.location.lng();
          this.address=place.formatted_address;
          this.lat=latitude;
          this.long=longitude;


        } else {
          console.log('No details available for input: ' + place.name);
        }
      });
    }
  } else {
    console.error('Geolocation is not supported or not running in a browser.');
  }
}



EditAndAddLocation(){
  if(this.mode=="edit"){
    const name = this.name || '';
    const address = this.address || '';
    this.LocationServ.EditByID(name,address,this.lat,this.long,this.id).subscribe(
      (d: any) => {
        this.dialogRef.close();

      },
      (error) => {
        console.log(error)
      }
    );
  }
  else if(this.mode=="add"){

    const name = this.name || '';
    const address = this.address || '';
    this.LocationServ.CreateAddress(name,address,this.lat,this.long).subscribe(
      (d: any) => {
        this.dialogRef.close();
      },
      (error) => {
        console.log(error)
      }
    );


  }
}

}

  





