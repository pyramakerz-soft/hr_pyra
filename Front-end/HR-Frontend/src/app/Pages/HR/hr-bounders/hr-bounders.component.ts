import { CommonModule } from '@angular/common';
import { Component } from '@angular/core';
import { BoundersPopUpComponent } from '../../../Components/bounders-pop-up/bounders-pop-up.component';
import { MatDialog } from '@angular/material/dialog';
import { Location } from '../../../Models/location';
import { LocationsService } from '../../../Services/locations.service';
import { FormsModule } from '@angular/forms';
import Swal from 'sweetalert2';

@Component({
  selector: 'app-hr-bounders',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './hr-bounders.component.html',
  styleUrls: ['./hr-bounders.component.css']
})
export class HrBoundersComponent {
  tableData: Location[] = [];
  PagesNumber: number = 1;
  CurrentPageNumber: number = 1;
  pages: number[] = [];
  locationsNames: string[] = [];
  filteredLocations: string[] = [];
  selectedName: string = "";
  DisplayPagginationOrNot: boolean = true;



  constructor(public dialog: MatDialog, public locationServ: LocationsService) { }

  ngOnInit() {
    this.getAllLocations(1);
    this.getLocationsName();
  }


  getAllLocations(page: number) {
    this.CurrentPageNumber = page;
    this.locationServ.getall(page).subscribe(
      (d: any) => {
        this.tableData = d.locations.data;
        this.PagesNumber = d.locations.last_page;
        this.generatePages();
      },
      (error) => {
      }
    );
  }

  generatePages() {
    this.pages = [];
    for (let i = 1; i <= this.PagesNumber; i++) {
      this.pages.push(i);
    }
  }

  openDialog(lat?: string, long?: string, EditedLocationName?: string, id?: number, EditedLocationAddress?: string): void {
    const dialogRef = this.dialog.open(BoundersPopUpComponent, {
      data: EditedLocationName
        ? {
          mode: 'edit',
          locationName: EditedLocationName,
          id: id,
          LocationAddress: EditedLocationAddress,
          Lat: lat,
          Long: long
        }
        : {
          mode: 'add',
        },
    });

    dialogRef.afterClosed().subscribe(result => {
      this.getAllLocations(this.CurrentPageNumber);
    });
  }

  deleteLocation(id: number) {
    Swal.fire({
      title: 'Are you sure you want to Delete This Location?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#FF7519',
      cancelButtonColor: '#17253E',
      confirmButtonText: 'Delete',
      cancelButtonText: 'Cancel'
    }).then((result) => {
      if (result.isConfirmed) {

        this.locationServ.DeleteByID(id).subscribe(result => {
          this.getAllLocations(1);
        });
      }
    });
  }

  getNextPage() {
    this.CurrentPageNumber++;
    this.getAllLocations(this.CurrentPageNumber);
  }

  getPrevPage() {
    this.CurrentPageNumber--;
    this.getAllLocations(this.CurrentPageNumber);
  }

  getLocationsName() {
    this.locationServ.GetAllNames().subscribe(
      (d: any) => {
        this.locationsNames = d.locationNames.map((item: { name: any; }) => item.name);

      },
      (error) => {
      }
    );
  }

  filterByName() {
    // this.getLocationsName();
    const query = this.selectedName.toLowerCase();
    if (query.trim() === '') {
      // If the input is empty, call getAllLocations with the current page number
      this.getAllLocations(this.CurrentPageNumber);
      this.DisplayPagginationOrNot = true
      this.filteredLocations = []; // Clear the dropdown list
    } else {
      this.filteredLocations = this.locationsNames;
      this.filteredLocations = this.locationsNames.filter(name =>
        name.toLowerCase().includes(query)
      );
    }
  }

  selectLocation(location: string) {
    this.selectedName = location;
    this.locationServ.SearchByNames(this.selectedName).subscribe(
      (d: any) => {
        this.tableData = d.locations;
        this.DisplayPagginationOrNot = false;
      },
      (error) => {
      }
    );

  }

  resetfilteredLocations() {
    this.filteredLocations = [];

  }



  Search() {
    if (this.selectedName) {
      this.locationServ.SearchByNames(this.selectedName).subscribe(
        (d: any) => {
          this.tableData = d.locations;
          this.PagesNumber = 1;
          this.DisplayPagginationOrNot = false;
          this.filteredLocations = [];
        },
        (error) => {
        }
      );
    }
    else {
      this.DisplayPagginationOrNot = true;
    }
  }

}
