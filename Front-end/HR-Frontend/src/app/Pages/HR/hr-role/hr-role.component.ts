import { CommonModule } from '@angular/common';
import { Component } from '@angular/core';
import { Router } from '@angular/router';

interface data{
  role:string,
  desc:string,
}

@Component({
  selector: 'app-hr-role',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './hr-role.component.html',
  styleUrl: './hr-role.component.css'
})

export class HrRoleComponent {
  constructor(private router: Router) {}
  
  tableData:data[]= [
    { role: "Employee", desc: 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. '},
    { role: "Employee", desc: 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. '},
    { role: "Employee", desc: 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. '},
    // Add more data as needed
  ];

  NavigateToAddRole(){
    this.router.navigateByUrl("/HR/HRRoleAdd");
  }
}
