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


  constructor(public router:Router , public userServ:UserServiceService){}

  tableData:UserModel[]= [];

  ngOnInit(){
    this.getAllEmployees(1);

  }

  NavigateToEmployeeAttendanceDetails(EmpId:number){
    this.router.navigateByUrl("HR/HREmployeeAttendanceDetails/"+EmpId)
  }


  getAllEmployees(pgNumber:number) {
    this.CurrentPageNumber = pgNumber;
    this.userServ.getall(pgNumber).subscribe(
      (d: any) => {
        console.log(d.data[0])
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
        this.PagesNumber=d.data[0].pagination.last_page;
        this.generatePages();
      },
      (error) => {
        console.log(error)
      }
    );
  }
  else{
    this.getAllEmployees(1);
  }
  }
}
