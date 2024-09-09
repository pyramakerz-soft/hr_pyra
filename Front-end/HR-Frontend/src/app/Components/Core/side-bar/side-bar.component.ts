import { CommonModule } from '@angular/common';
import { Component, Input } from '@angular/core';
import { Router, RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';
import { AccountService } from '../../../Services/account.service';
import Swal from 'sweetalert2';

@Component({
  selector: 'app-side-bar',
  standalone: true,
  imports: [RouterLink, RouterOutlet, RouterLinkActive,CommonModule],
  templateUrl: './side-bar.component.html',
  styleUrl: './side-bar.component.css'
})
export class SideBarComponent {

  @Input() menuItems: { label: string; icon: string; route: string; }[] = [];
  @Input() closeMenu!: () => void;

  constructor(public AccountServ:AccountService ,private router: Router){}

  activeIndex: number | null = 0;
  
  ngOnInit(): void {
    this.setActiveIndexByRoute(this.router.url);
  }
  
  setActiveIndex(index: number): void {
    this.activeIndex = index;
    if (window.innerWidth < 1024) {
      this.closeMenu();
    }
  }

  setActiveIndexByRoute(currentRoute: string): void {
    const foundIndex = this.menuItems.findIndex(item => currentRoute.includes(item.route));
    if (foundIndex !== -1) {
      this.setActiveIndex(foundIndex);
    }
  }

  signOut(){
    Swal.fire({
      title: 'Are you sure you want to sign out?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#FF7519',
      cancelButtonColor: '#17253E',
      confirmButtonText: 'Sign Out',
      cancelButtonText: 'Cancel'
    }).then((result) => {
      if (result.isConfirmed) {
        this.AccountServ.logout();
        if (window.innerWidth < 1024) { 
          this.closeMenu();
        }
      }
    });
  }
}