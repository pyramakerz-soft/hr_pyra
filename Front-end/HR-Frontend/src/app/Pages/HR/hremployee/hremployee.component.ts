import { Component } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { SideBarComponent } from '../../../Components/Core/side-bar/side-bar.component';
import { CommonModule } from '@angular/common';
import { MatDialog } from '@angular/material/dialog';
import { ImportEmployeeDataPopUpComponent } from '../../../Components/import-employee-data-pop-up/import-employee-data-pop-up.component';
import { Router } from '@angular/router';
import { UserModel } from '../../../Models/user-model';
import { UserServiceService } from '../../../Services/user-service.service';
import Swal from 'sweetalert2';
import { ClockService } from '../../../Services/clock.service';

interface data {
  Name: string,
  Code: string,
  Department: string,
  position: string,
  phone: string,
  Email: string,
  UserName: string,
}

@Component({
  selector: 'app-hremployee',
  standalone: true,
  imports: [CommonModule, FormsModule, SideBarComponent],
  templateUrl: './hremployee.component.html',
  styleUrl: './hremployee.component.css'
})
export class HREmployeeComponent {

  constructor(public dialog: MatDialog, private router: Router, public userServ: UserServiceService , private clockService: ClockService) { }

  tableData: UserModel[] = [];
  isMenuOpen: boolean = false;
  PagesNumber: number = 1;
  CurrentPageNumber: number = 1;
  pages: number[] = [];
  selectedName: string = "";
  DisplayPagginationOrNot: boolean = true;
  UsersNames: string[] = [];
  filteredUsers: string[] = [];
  isLoading: boolean = false; // Add isLoading state

  isNavigateingToImportPopUp = false

  ngOnInit() {

    const savedPageNumber = localStorage.getItem('HrEmployeeCN');
    if (savedPageNumber) {
      this.CurrentPageNumber = parseInt(savedPageNumber, 10);
    } else {
      this.CurrentPageNumber = 1; // Default value if none is saved
    }
    this.getAllEmployees(this.CurrentPageNumber);
    this.getUsersName()

    localStorage.setItem('HrLocationsCN', "1");
    localStorage.setItem('HrAttendaceCN', "1");
    localStorage.setItem('HrAttanceDetailsCN', "1");


  }

   downloadExcelTemplate() {
    this.isLoading = true; // Show spinner
    this.clockService.downloadAllUsersExcel().subscribe(
      (blob: Blob) => {
        this.isLoading = false; // Hide spinner
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'all_users.xlsx';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
      },
      (error) => {
        this.isLoading = false; // Hide spinner on error
        if (error.status === 404) {
          Swal.fire({
            text: "No user records found.",
            confirmButtonText: "OK",
            confirmButtonColor: "#FF7519",
          });
        } else {
          Swal.fire({
            text: "An error occurred while downloading the Excel file. Please try again.",
            confirmButtonText: "OK",
            confirmButtonColor: "#FF7519",
          });
        }
      }
    );
  }

  OpenMenu() {
    this.isMenuOpen = !this.isMenuOpen;
  }

  OpenImportPopUp() {
    this.isNavigateingToImportPopUp = true
    const dialogRef = this.dialog.open(ImportEmployeeDataPopUpComponent, {
    });
    dialogRef.afterClosed().subscribe(() => {
      this.isNavigateingToImportPopUp = false;
    });

  }

  NavigateToAddEmployee() {
    this.router.navigateByUrl("HR/HREmployeeDetailsAdd")
  }

  NavigateToEmployeeDetails(id: number) {
    this.router.navigateByUrl(`HR/HREmployeeDetails/${id}`)
  }

  NavigateToEditEmployee(empId: number) {
    this.router.navigateByUrl(`HR/HREmployeeDetailsEdit/${empId}`)
  }

  getAllEmployees(PgNumber: number) {
    this.CurrentPageNumber = PgNumber;
    this.saveCurrentPageNumber();

    this.userServ.getall(PgNumber).subscribe(
      (d: any) => {
        this.tableData = d.data.users;
        this.PagesNumber = d.data.pagination.last_page;
        this.generatePages();
      }
    );
  }

  generatePages() {
    this.pages = [];
    for (let i = 1; i <= this.PagesNumber; i++) {
      this.pages.push(i);
    }
  }

  getNextPage() {
    this.CurrentPageNumber++;
    this.saveCurrentPageNumber();
    this.getAllEmployees(this.CurrentPageNumber);
  }

  getPrevPage() {
    this.CurrentPageNumber--;
    this.saveCurrentPageNumber();
    this.getAllEmployees(this.CurrentPageNumber);
  }

  saveCurrentPageNumber() {
    localStorage.setItem('HrEmployeeCN', this.CurrentPageNumber.toString());
  }


  Search() {
    if (this.selectedName) {
      this.userServ.SearchByName(this.selectedName).subscribe(
        (d: any) => {
          this.tableData = d.data.users;
          this.PagesNumber = 1;
          this.DisplayPagginationOrNot = false;
          this.filteredUsers = [];
        }
      );
    }
    else {
      this.DisplayPagginationOrNot = true;
    }
  }


  getUsersName() {
    this.userServ.getAllUsersName().subscribe(
      (d: any) => {
        this.UsersNames = d.usersNames;
      }
    );
  }


  filterByName() {
    // this.getLocationsName();
    const query = this.selectedName.toLowerCase();
    if (query.trim() === '') {
      // If the input is empty, call getAllLocations with the current page number
      this.getAllEmployees(this.CurrentPageNumber);
      this.DisplayPagginationOrNot = true;
      this.filteredUsers = []; // Clear the dropdown list
    } else {
      this.filteredUsers = this.UsersNames;
      this.filteredUsers = this.UsersNames.filter(name =>
        name.toLowerCase().includes(query)
      );
    }
  }

  selectUser(location: string) {
    this.selectedName = location;
    this.userServ.SearchByName(this.selectedName).subscribe(
      (d: any) => {
        this.tableData = d.data.users;
        this.DisplayPagginationOrNot = false;
      },
    );

  }

  resetfilteredUsers() {
    this.filteredUsers = [];

  }


  DeleteEmp(id: number) {

    Swal.fire({
      title: 'Are you sure you want to Delete This Employee?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#FF7519',
      cancelButtonColor: '#17253E',
      confirmButtonText: 'Delete',
      cancelButtonText: 'Cancel'
    }).then((result) => {
      if (result.isConfirmed) {
        this.userServ.DeleteById(id).subscribe(
          (d: any) => {
            if(this.tableData.length==1&&this.CurrentPageNumber-1>=1){
              this.getAllEmployees(this.CurrentPageNumber-1);
            }
            else{
              this.getAllEmployees(this.CurrentPageNumber);
            }
            this.getUsersName()
          }
        );

      }
    });
  }
}