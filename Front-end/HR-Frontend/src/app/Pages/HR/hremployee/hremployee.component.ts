import { Component } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { SideBarComponent } from '../../../Components/Core/side-bar/side-bar.component';
import { CommonModule } from '@angular/common';
import { MatDialog } from '@angular/material/dialog';
import { ImportEmployeeDataPopUpComponent } from '../../../Components/import-employee-data-pop-up/import-employee-data-pop-up.component';
import { Router } from '@angular/router';

interface data{
  Name:string,
  Code:string,
  Department:string,
  position:string,
  phone:string,
  Email:string,
  UserName:string,
}

@Component({
  selector: 'app-hremployee',
  standalone: true,
  imports: [CommonModule,FormsModule,SideBarComponent],
  templateUrl: './hremployee.component.html',
  styleUrl: './hremployee.component.css'
})
export class HREmployeeComponent {
  constructor(public dialog: MatDialog,private router: Router){}

  tableData: data[] = [
    {
      Name: "John Doe",
      Code: "EMP001",
      Department: "Software",
      position: "HR Manager",
      phone: "+1234567890",
      Email: "johndoe@example.com",
      UserName: "johndoe"
    },
    {
      Name: "Jane Smith",
      Code: "EMP002",
      Department: "Graphic",
      position: "Marketing Coordinator",
      phone: "+0987654321",
      Email: "janesmith@example.com",
      UserName: "janesmith"
    }
  ];
  isMenuOpen: boolean = false;

  OpenMenu() {
    this.isMenuOpen = !this.isMenuOpen;
  }

  OpenImportPopUp(){
    this.dialog.open(ImportEmployeeDataPopUpComponent, {

    });
  }

  NavigateToAddEmployee(){
    this.router.navigateByUrl("HR/HREmployeeDetailsAdd")
  }
  
  NavigateToEmployeeDetails(){
    this.router.navigateByUrl("HR/HREmployeeDetails")
  }
 
  NavigateToEditEmployee(empId:number){
    this.router.navigateByUrl(`HR/HREmployeeDetailsEdit/${empId}`)
  }
}
