import { Component, OnInit } from '@angular/core';
import { UserModel } from '../../Models/user-model';
import { UserServiceService } from '../../Services/user-service.service';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-user',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './user.component.html',
  styleUrl: './user.component.css'
})
export class UserComponent implements OnInit {
  users: UserModel[] = []
  response: any

  constructor(public userService: UserServiceService){}

  ngOnInit(): void {
    this.UploadData()
  }

  UploadData(){
    this.userService.GetAllusers().subscribe({
      next: (users) => {
        this.response = users
        console.log(this.response.data.users)
        this.users = this.response.data.users

        for(let i=0; i < this.response.length; i++){
          for(let j=0; j < this.response.roles.length; j++){
            this.users[i].roleId[j] = this.response.data.users.roles.id
            this.users[i].roleName[j] = this.response.data.users.roles.name
          }
        }

        console.log(this.users)
      },
      error: (error) => {
        console.log(error);
      }
    })
  }
}
