import { Component, OnInit } from '@angular/core';
import { UserModel } from '../../Models/user-model';
import { UserServiceService } from '../../Services/user-service.service';

@Component({
  selector: 'app-user',
  standalone: true,
  imports: [],
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
        // this.users = users
        // console.log(this.users)
        this.response = users
        console.log(this.response)
      },
      error: (error) => {
        console.log(error);
      }
    })
  }
}
