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

  constructor(public userService: UserServiceService){}

  ngOnInit(): void {
    this.UploadData()
  }

  UploadData(){
    this.userService.GetAllusers().subscribe({
      next: (users:any) => {
        this.users = users.data.users
        console.log(this.users)
      },
      error: (error) => {
        console.log(error);
      }
    })
  }

  DeleteUser(id: number){
    console.log(id)
    this.userService.DeleteUser(id).subscribe({
      next: () => {
        this.users = this.users.filter(user => user.id !== id);
      },
      error: (error) => {
        console.log(error);
      }
    })
  }
}
