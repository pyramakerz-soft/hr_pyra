import { Component } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { SideBarComponent } from '../../../Components/Core/side-bar/side-bar.component';
import { CommonModule } from '@angular/common';
import { MatDialog } from '@angular/material/dialog';
import { ImportEmployeeDataPopUpComponent } from '../../../Components/import-employee-data-pop-up/import-employee-data-pop-up.component';
import { Router } from '@angular/router';
import { UserModel } from '../../../Models/user-model';
import { UserServiceService } from '../../../Services/user-service.service';

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
  constructor(public dialog: MatDialog, private router: Router, public userServ: UserServiceService) { }

  tableData: UserModel[] = [];
  isMenuOpen: boolean = false;
  PagesNumber: number = 1;
  CurrentPageNumber: number = 1;
  pages: number[] = [];
  selectedName: string = "";
  DisplayPagginationOrNot:boolean=true;
  UsersName:string[]=[];
  filteredUsers: string[] = [];


  ngOnInit() {
    this.getAllEmployees(this.CurrentPageNumber);
    this.getUsersName()
  }


  OpenMenu() {
    this.isMenuOpen = !this.isMenuOpen;
  }

  OpenImportPopUp() {
    this.dialog.open(ImportEmployeeDataPopUpComponent, {

    });
  }

  NavigateToAddEmployee(){
    this.router.navigateByUrl("HR/HREmployeeDetailsAdd")
  }

  NavigateToEmployeeDetails(id:number) {
    this.router.navigateByUrl(`HR/HREmployeeDetails/${id}`)
  }
 
  NavigateToEditEmployee(empId:number){
    this.router.navigateByUrl(`HR/HREmployeeDetailsEdit/${empId}`)
  }

  getAllEmployees(PgNumber:number) {
    this.CurrentPageNumber=PgNumber;
    this.userServ.getall(PgNumber).subscribe(
      (d: any) => {
        this.tableData = d.data[0].users;
        this.PagesNumber=d.data[0].pagination.last_page;
        this.generatePages();
      },
      (error) => {
        console.log(error)
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
    this.getAllEmployees(this.CurrentPageNumber);
  }

  getPrevPage() {
    this.CurrentPageNumber--;
    this.getAllEmployees(this.CurrentPageNumber);
  }

  Search(){
    if(this.selectedName){
    this.userServ.SearchByName(this.selectedName).subscribe(
      (d: any) => {
        this.tableData = d.data[0].users;
        this.PagesNumber=1;
        this.DisplayPagginationOrNot=false;
      },
      (error) => {
        console.log(error)
      }
    );
  }
  else{
    this.DisplayPagginationOrNot=true;
  }
  }

  getUsersName(){
    this.userServ.getAllUsersName().subscribe(
      (d: any) => {
        console.log(d)
      },
      (error) => {
        console.log(error)
      }
    );
  }


  filterByName() {
    // this.getLocationsName();
    const query = this.selectedName.toLowerCase();
    if (query.trim() === '') {
      // If the input is empty, call getAllLocations with the current page number
      this.getAllEmployees(this.CurrentPageNumber);
      this.filteredUsers = []; // Clear the dropdown list
    } else {
    this.filteredUsers = this.UsersName;
    this.filteredUsers = this.UsersName.filter(name => 
      name.toLowerCase().includes(query)
    );
  }
  }

  selectUser(location: string) {
    this.selectedName = location;
    this.userServ.SearchByName(this.selectedName).subscribe(
      (d: any) => {
        console.log(d.usersnames)
        this.tableData = d.locations.data;
      },
      (error) => {
        console.log(error);
      }
    );

  }

  resetfilteredUsers(){
    this.filteredUsers = [];

  }





  }


