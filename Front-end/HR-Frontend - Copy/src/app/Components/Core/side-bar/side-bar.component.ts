import { CommonModule } from '@angular/common';
import { Component, Input } from '@angular/core';
import { Router, RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';
import { AccountService } from '../../../Services/account.service';

@Component({
  selector: 'app-side-bar',
  standalone: true,
  imports: [RouterLink, RouterOutlet, RouterLinkActive,CommonModule],
  templateUrl: './side-bar.component.html',
  styleUrl: './side-bar.component.css'
})
export class SideBarComponent {

  @Input() menuItems: { label: string; icon: string; route: string; }[] = [];
  constructor(public AccountServ:AccountService ,private router: Router){

  }

  activeIndex: number | null = null;

  setActiveIndex(index: number): void {
    this.activeIndex = index;
  }

  ngOnInit(): void {
    this.setActiveIndex(0);
  }

  signOut(){

  this.AccountServ.logout();
  this.router.navigateByUrl("Login");

  }
}