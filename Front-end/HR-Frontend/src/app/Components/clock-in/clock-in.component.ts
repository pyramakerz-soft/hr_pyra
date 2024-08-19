import { Component } from '@angular/core';
import { MatDialog, MatDialogModule } from '@angular/material/dialog'
import { ClockInPopUpComponent } from '../clock-in-pop-up/clock-in-pop-up.component';
import { AccountService } from '../../Services/account.service';

@Component({
  selector: 'app-clock-in',
  standalone: true,
  imports: [
    MatDialogModule
    
  ],
  templateUrl: './clock-in.component.html',
  styleUrl: './clock-in.component.css'
})
export class ClockInComponent {
  userDetails: any ;
  currentDate: string | undefined;


  constructor(public dialog: MatDialog , public accountService:AccountService) {
  }

  ngOnInit(): void {
    this.userDetails = this.accountService.r;
    this.currentDate = this.getCurrentDate();
    
  }

  getCurrentDate(): string {
    const date = new Date();
    return date.toLocaleDateString('en-US', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    });
  }

  openDialog(): void {
    this.dialog.open(ClockInPopUpComponent, {

    });
  }
  // openDialog(): void {
  //   this.dialog.open(ClockInPopUpComponent, {
  //     width: '300px',
  //     data: { message: 'Hello from ClockInComponent' }
  //   });
  // }
}

