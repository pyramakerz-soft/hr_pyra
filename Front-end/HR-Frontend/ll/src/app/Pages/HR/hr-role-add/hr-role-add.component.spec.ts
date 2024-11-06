import { ComponentFixture, TestBed } from '@angular/core/testing';

import { HrRoleAddComponent } from './hr-role-add.component';

describe('HrRoleAddComponent', () => {
  let component: HrRoleAddComponent;
  let fixture: ComponentFixture<HrRoleAddComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [HrRoleAddComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(HrRoleAddComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
