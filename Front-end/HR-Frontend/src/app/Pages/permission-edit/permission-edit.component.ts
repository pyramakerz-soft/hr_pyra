import { Component } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { PermissionsService } from '../../Services/permissions.service';
import { PermissionModel } from '../../Models/permission-model';

@Component({
  selector: 'app-permission-edit',
  standalone: true,
  imports: [FormsModule],
  templateUrl: './permission-edit.component.html',
  styleUrl: './permission-edit.component.css'
})
export class PermissionEditComponent {
  constructor(public router:Router,public permissionsService:PermissionsService,public activatedroute:ActivatedRoute){}
  editedPermission:PermissionModel=new PermissionModel(0,"");

  ngOnInit(): void {
    this.activatedroute.params.subscribe(p=>{
      this.permissionsService.GetByID(p['id']).subscribe(d=>{
        this.editedPermission=d;
      })
    })
  }

  Save(){
    this.permissionsService.Update(this.editedPermission.id,this.editedPermission).subscribe(d=>{
      console.log(d);
      this.router.navigateByUrl("/Permissions")
    })
  }

}
