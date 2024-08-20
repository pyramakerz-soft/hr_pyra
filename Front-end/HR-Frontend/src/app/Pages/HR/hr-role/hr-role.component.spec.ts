import { ComponentFixture, TestBed } from '@angular/core/testing';

import { HrRoleComponent } from './hr-role.component';

describe('HrRoleComponent', () => {
  let component: HrRoleComponent;
  let fixture: ComponentFixture<HrRoleComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [HrRoleComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(HrRoleComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
