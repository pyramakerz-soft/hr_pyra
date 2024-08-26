import { CommonModule } from '@angular/common';
import { Component } from '@angular/core';
import { Router } from '@angular/router';
import { UserModel } from '../../../Models/user-model';
import { UserServiceService } from '../../../Services/user-service.service';
import { FormsModule } from '@angular/forms';

interface data{
  Employees:string,
  Department:string,
  Position:string,
}

@Component({
  selector: 'app-hr-attendance',
  standalone: true,
  imports: [CommonModule , FormsModule],
  templateUrl: './hr-attendance.component.html',
  styleUrl: './hr-attendance.component.css'
})
export class HrAttendanceComponent {
  PagesNumber: number = 1;
  CurrentPageNumber: number = 1;
  pages: number[] = [];
  selectedName: string = "";
  DisplayPagginationOrNot:boolean=true;
  UsersNames:string[]=[];
  filteredUsers: string[] = [];

  constructor(public router:Router , public userServ:UserServiceService){}

  tableData:UserModel[]= [];

  ngOnInit(){
    this.getAllEmployees(1);
    this.getUsersName();

  }

  NavigateToEmployeeAttendanceDetails(EmpId:number){
    this.router.navigateByUrl("HR/HREmployeeAttendanceDetails/"+EmpId)
  }


  getAllEmployees(pgNumber:number) {
    this.CurrentPageNumber = pgNumber;
    this.userServ.getall(pgNumber).subscribe(
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
        this.filteredUsers=[];
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
        this.UsersNames=d.usersNames;
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
      this.DisplayPagginationOrNot=true;
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
        this.tableData=d.data[0].users;
        this.DisplayPagginationOrNot=false;
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
