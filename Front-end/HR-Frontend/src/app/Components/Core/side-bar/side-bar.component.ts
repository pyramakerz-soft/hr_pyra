import { CommonModule } from '@angular/common';
import { Component, Input } from '@angular/core';
import { Router, RouterLink, RouterLinkActive, RouterOutlet, NavigationEnd  } from '@angular/router';
import { AccountService } from '../../../Services/account.service';
import Swal from 'sweetalert2';
import { Subscription } from 'rxjs';
import { IssuesService } from '../../../Services/issues.service';
import { IssueNotificationService } from '../../../Services/issue-notification.service';

@Component({
  selector: 'app-side-bar',
  standalone: true,
  imports: [RouterLink, RouterOutlet, RouterLinkActive, CommonModule],
  templateUrl: './side-bar.component.html',
  styleUrl: './side-bar.component.css'
})
export class SideBarComponent {

  @Input() menuItems: { label: string; icon: string; route: string; notificationCount:number}[] = [];
  @Input() closeMenu!: () => void;

  issueCount:number=0;
  constructor(public AccountServ:AccountService ,private router: Router ,public IssueServ : IssuesService ,private issueNotificationService: IssueNotificationService){}

  activeIndex: number | null = 0;

  private routerSubscription!: Subscription;
  
  ngOnInit(): void {
    this.setActiveIndexByRoute(this.router.url);
    this.IssueServ.GetIssueCount().subscribe(
      (d:any)=>{
        this.issueCount = d.data.count;
      });
    this.issueNotificationService.menuItems$.subscribe((count: number) => {
      this.IssueServ.GetIssueCount().subscribe(
        (d:any)=>{
          this.issueCount = d.data.count;
        });
    });

    // Subscribe to router events
    this.routerSubscription = this.router.events.subscribe(event => {
      if (event instanceof NavigationEnd) {
        this.setActiveIndexByRoute(event.urlAfterRedirects);
      }
    });
  }

  ngOnDestroy(): void {
    // Clean up subscription
    if (this.routerSubscription) {
      this.routerSubscription.unsubscribe();
    }
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